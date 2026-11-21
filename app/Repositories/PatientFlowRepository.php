<?php

namespace App\Repositories;

use App\Models\DoctorDepartment;
use App\Models\ReceiptLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Patient flow ("État de passage des patients"): every paid item of a period, from the
 * receipts, with its payer and service, and the summaries the old program prints.
 */
class PatientFlowRepository
{
    const TABS = ['detailed', 'general', 'patient_shares', 'without_insurance', 'by_service'];

    public function filters(array $input): array
    {
        $date = function ($value, Carbon $default) {
            try {
                return $value ? Carbon::parse($value) : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        };
        $from = $date($input['from'] ?? null, today())->startOfDay();
        $to = $date($input['to'] ?? null, today())->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'from' => $from,
            'to' => $to,
            'tab' => in_array($input['tab'] ?? '', self::TABS, true) ? $input['tab'] : 'detailed',
            'department' => (int) ($input['department'] ?? 0) ?: null,
        ];
    }

    /** One row per receipt line of the period (cancelled receipts included, flagged). */
    public function rows(array $filters): Collection
    {
        $lines = ReceiptLine::with([
            'receipt.patient.patientUser', 'receipt.user', 'receipt.refund',
        ])->whereHas('receipt', fn ($q) => $q->whereBetween('created_at', [$filters['from'], $filters['to']]))->get();

        $lines->each(function ($line) {
            $line->setRelation('item', match ($line->payable_type) {
                'opd_visit' => \App\Models\OpdPatientDepartment::with('doctor.doctorUser', 'doctor.department', 'patientInsurance.insurance')->find($line->payable_id),
                'exam_item' => \App\Models\ExamOrderItem::with('order.doctor.doctorUser', 'order.patientInsurance.insurance')->find($line->payable_id),
                default => null,
            });
        });

        $rows = $lines->map(function ($line) {
            $item = $line->item;
            $isVisit = $line->payable_type === 'opd_visit';
            $isExam = $line->payable_type === 'exam_item';
            $doctor = $isVisit ? $item?->doctor : ($isExam ? $item?->order?->doctor : null);
            $policy = $isVisit ? $item?->patientInsurance : ($isExam ? $item?->order?->patientInsurance : null);
            $receipt = $line->receipt;

            return [
                'date' => $receipt->created_at,
                'receipt_number' => $receipt->receipt_number,
                'patient_number' => $receipt->patient->patient_unique_id ?? '',
                'patient' => $receipt->patient->patientUser->full_name ?? '',
                'nature' => $line->description,
                'doctor' => $doctor?->doctorUser?->full_name,
                'department_id' => $isVisit ? $doctor?->doctor_department_id : null,
                'service' => ! $isVisit && ! $isExam ? __('messages.cashier.'.$line->payable_type) : ($isVisit ? ($doctor?->department?->title ?? __('messages.common.n/a'))
                    : __('messages.exam_orders.'.(($item?->testable_type ?? '') === 'radiology_test' ? 'imaging' : (($item?->testable_type ?? '') === 'package' ? 'packages' : 'laboratory')))),
                'payer' => $policy?->insurance?->name,
                'total' => $line->total,
                'discount' => $line->discount_amount,
                'payer_amount' => $line->payer_amount,
                'paid' => $line->amount,
                'operator' => $receipt->user->full_name ?? '',
                'status' => $receipt->status,
            ];
        })->sortBy('date')->values();

        return $rows;
    }

    /** Rows of the chosen tab, and the totals. */
    public function report(array $filters): array
    {
        $all = $this->rows($filters)->when($filters['department'], fn ($rows, $dep) => $rows->where('department_id', $dep)->values());
        $valid = $all->where('status', 'valid')->values();
        $totals = fn (Collection $rows) => [
            'count' => $rows->count(), 'total' => $rows->sum('total'), 'discount' => $rows->sum('discount'),
            'payer_amount' => $rows->sum('payer_amount'), 'paid' => $rows->sum('paid'),
        ];
        $group = fn (string $key) => $valid->groupBy($key)->map($totals)->sortKeys();

        return match ($filters['tab']) {
            'general' => ['groups' => $group('nature'), 'totals' => $totals($valid)],
            'by_service' => ['groups' => $group('service'), 'totals' => $totals($valid)],
            'patient_shares' => ['rows' => $s = $valid->where('payer_amount', '>', 0)->values(), 'totals' => $totals($s)],
            'without_insurance' => ['rows' => $s = $valid->where('payer_amount', 0)->values(), 'totals' => $totals($s)],
            default => ['rows' => $all, 'totals' => $totals($valid)],
        };
    }

    public static function departments(): array
    {
        return DoctorDepartment::orderBy('title')->pluck('title', 'id')->all();
    }
}
