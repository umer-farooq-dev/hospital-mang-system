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
 * total, reduction (payer discount and department discount, one on top of the other),
 * then the payer's share of the rest within the coverage rule and the policy limits.
 */
class PriceSplit
{
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

        // Reduction: discounts compound (each applies to what is left), like "réduction sur réduction".
        $discounts = array_filter([
            $policy?->insurance->discount,
            $departmentId ? DoctorDepartment::whereKey($departmentId)->value('discount_percent') : null,
        ]);
        $kept = array_reduce($discounts, fn ($left, $d) => $left * (1 - min(100, (float) $d) / 100), 1.0);
        $discountPercent = round((1 - $kept) * 100, 2);
        $net = self::money($gross * $kept);

        // ponytail: discounts always apply before the payer's share, as on the old receipt;
        // make the order a setting if the client's answer (question 5) says otherwise.
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
                $payer = $net * $coverage / 100;
                $caps = array_filter([$rule?->max_amount, $policy->visit_limit, self::annualLeft($policy, $date, $options['ignore_visit_id'] ?? null)],
                    fn ($cap) => $cap !== null);
                if ($caps && $payer > min($caps)) {
                    $payer = max(0, min($caps));
                    $warnings[] = 'limit_reached';
                }
            }
        }
        $payer = self::money($payer);

        return [
            'gross' => self::money($gross),
            'discount_percent' => $discountPercent,
            'discount_amount' => self::money($gross - $net),
            'net' => $net,
            'coverage_percent' => $coverage,
            'payer_amount' => $payer,
            'patient_amount' => self::money($net - $payer),
            'warnings' => $warnings,
        ];
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
