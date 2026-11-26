<?php

namespace App\Utils;

use App\Models\CoverageRule;
use App\Models\DoctorDepartment;
use App\Models\ExamOrderItem;
use App\Models\OpdPatientDepartment;
use App\Models\PatientInsurance;
use Illuminate\Support\Carbon;

/**
 * Splits an amount between the payer and the patient, as on the old receipt:
 * total, reduction (payer discount and department discount), then the payer's share
 * within the coverage rule and the policy limits. The admin sets (cash counters page) whether the
 * reduction comes before the payer's share or only lowers the patient's part, whether discounts
 * compound or add up, and the rounding of the patient's share.
 */
class PriceSplit
{
    const ORDERS = ['discount_first', 'payer_first'];

    const COMBINE = ['compound', 'add'];

    const ROUNDING_MODES = ['nearest', 'up', 'down'];

    public static function rules(): array
    {
        $v = \App\Models\Setting::whereIn('key', ['split_order', 'split_discount_combine', 'split_rounding', 'split_rounding_mode'])->pluck('value', 'key');

        return [
            'order' => in_array($v['split_order'] ?? '', self::ORDERS, true) ? $v['split_order'] : 'discount_first',
            'combine' => in_array($v['split_discount_combine'] ?? '', self::COMBINE, true) ? $v['split_discount_combine'] : 'compound',
            'step' => max(0, (float) ($v['split_rounding'] ?? 0)),
            'mode' => in_array($v['split_rounding_mode'] ?? '', self::ROUNDING_MODES, true) ? $v['split_rounding_mode'] : 'nearest',
        ];
    }

    public static function roundTo(float $amount, float $step, string $mode): float
    {
        if ($step <= 0) {
            return $amount;
        }
        $units = $amount / $step;
        $units = match ($mode) {
            'up' => ceil(round($units, 6)),
            'down' => floor(round($units, 6)),
            default => round($units),
        };

        return $units * $step;
    }
    /**
     * @return array{gross: float, discount_percent: float, discount_amount: float, net: float,
     *               coverage_percent: float, payer_amount: float, patient_amount: float, warnings: string[]}
     */
    public static function compute(float $gross, ?PatientInsurance $policy, ?int $departmentId, array $options = []): array
    {
        $date = Carbon::parse($options['date'] ?? now());
        $warnings = [];
        if ($policy && ! $policy->isValidOn($date)) {
            $warnings[] = 'policy_not_valid';
            $policy = null;
        }

        $rules = self::rules();
        // Reduction: compound ("réduction sur réduction", each applies to what is left) or added up.
        $discounts = array_filter([
            $policy?->insurance->discount,
            $departmentId ? DoctorDepartment::whereKey($departmentId)->value('discount_percent') : null,
            // reduction granted at the desk (Reception: one visit), capped in Settings > Reception
            $options['extra_discount'] ?? null,
        ]);
        $kept = $rules['combine'] === 'add'
            ? max(0, 1 - min(100, array_sum(array_map('floatval', $discounts))) / 100)
            : array_reduce($discounts, fn ($left, $d) => $left * (1 - min(100, (float) $d) / 100), 1.0);
        // Discount first (old receipt): the payer's share is taken on the reduced amount.
        // Payer first: the payer's share is taken on the full amount and the reduction only lowers the patient's part.
        $base = $rules['order'] === 'payer_first' ? $gross : $gross * $kept;
        $coverage = 0.0;
        $payer = 0.0;
        if ($policy) {
            $rule = CoverageRule::match($policy->insurance_id, $departmentId, $options['charge_category_id'] ?? null);
            if ($rule?->not_covered) {
                $warnings[] = 'not_covered';
            } elseif ($rule?->needs_approval && blank($options['voucher_number'] ?? null)) {
                $warnings[] = 'approval_needed';
            } else {
                $coverage = (float) ($rule?->coverage_percent ?? $policy->effective_coverage ?? 0);
                $payer = $base * $coverage / 100;
                $caps = array_filter([$rule?->max_amount, $policy->visit_limit, self::annualLeft($policy, $date, $options['ignore_visit_id'] ?? null)],
                    fn ($cap) => $cap !== null);
                if ($caps && $payer > min($caps)) {
                    $payer = max(0, min($caps));
                    $warnings[] = 'limit_reached';
                }
            }
        }
        $payer = self::money($payer);
        $patient = $rules['order'] === 'payer_first' ? ($gross - $payer) * $kept : $base - $payer;
        // Rounding applies to what the patient pays; the payer's share takes the difference (never below zero).
        $rounded = self::money(self::roundTo($patient, $rules['step'], $rules['mode']));
        if ($payer > 0) {
            $payer = self::money(max(0, $payer - ($rounded - $patient)));
        }
        $patient = $rounded;
        $net = self::money($payer + $patient);

        return [
            'gross' => self::money($gross),
            'discount_percent' => $gross > 0 ? round(($gross - $net) / $gross * 100, 2) : 0.0,
            'discount_amount' => self::money($gross - $net),
            'net' => $net,
            'coverage_percent' => $coverage,
            'payer_amount' => $payer,
            'patient_amount' => $patient,
            'warnings' => $warnings,
        ];
    }

    /** With the setting "policy not valid: refuse", a visit or exam with an invalid policy is not saved. */
    public static function assertPolicyAllowed(array $split): void
    {
        if (in_array('policy_not_valid', $split['warnings'])
            && \App\Models\Setting::where('key', 'policy_expired_mode')->value('value') === 'block') {
            throw \Illuminate\Validation\ValidationException::withMessages(['patient_insurance_id' => __('messages.split.warnings.policy_blocked')]);
        }
    }

    /** What is left of the policy's annual limit, or null when it has none. */
    private static function annualLeft(PatientInsurance $policy, Carbon $date, $ignoreVisitId): ?float
    {
        if ($policy->annual_limit === null) {
            return null;
        }
        $used = OpdPatientDepartment::where('patient_insurance_id', $policy->id)
            ->whereYear('appointment_date', $date->year)
            ->when($ignoreVisitId, fn ($q) => $q->where('id', '!=', $ignoreVisitId))
            ->sum('payer_amount');
        $used += ExamOrderItem::whereHas('order', fn ($q) => $q->where('patient_insurance_id', $policy->id)->whereYear('ordered_at', $date->year))
            ->sum('payer_amount');

        return max(0, $policy->annual_limit - (float) $used);
    }

    private static function money(float $amount): float
    {
        return round($amount, currencyFormatSettings()['decimals']);
    }
}
