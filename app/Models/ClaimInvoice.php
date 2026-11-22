<?php

namespace App\Models;

use App\Utils\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Invoice to an insurer for the payer's share of acts, grouped as the insurer's billing option says:
 * one invoice per act (per_visit), per admission or patient and day (per_admission), or one per period (monthly).
 */
class ClaimInvoice extends Model
{
    const ISSUED = 'issued';

    const PARTIAL = 'partial';

    const PAID = 'paid';

    const CLOSED = 'closed';        // nothing left to collect, not all paid (rejections, write-offs)

    const CANCELLED = 'cancelled';

    const STATUSES = [self::ISSUED, self::PARTIAL, self::PAID, self::CLOSED, self::CANCELLED];

    protected $fillable = ['number', 'insurance_id', 'billing_option', 'period_from', 'period_to', 'issued_on', 'due_on', 'status',
        'total_claimed', 'note', 'created_by', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'issued_on' => 'date', 'due_on' => 'date', 'total_claimed' => 'float', 'cancelled_at' => 'datetime'];

    public function lines(): HasMany
    {
        return $this->hasMany(ClaimLine::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function outstanding(): float
    {
        return round($this->lines->sum(fn ($line) => $line->outstanding()), 2);
    }

    /**
     * Number from the admin's pattern, e.g. "{seq}/{mm}/{yy}/SUKA" gives 0002/11/26/SUKA.
     * The sequence restarts each month when the pattern has {mm}, each year with {yy}/{yyyy}, else never.
     */
    public static function formatNumber(string $pattern, int $sequence, Carbon $date, int $digits): string
    {
        return strtr($pattern ?: '{seq}', ['{seq}' => str_pad((string) $sequence, max(1, $digits), '0', STR_PAD_LEFT),
            '{mm}' => $date->format('m'), '{yy}' => $date->format('y'), '{yyyy}' => $date->format('Y')]);
    }

    public static function newNumber(Carbon $date): string
    {
        $pattern = (string) Stock::setting('claim_invoice_format', '{seq}/{mm}/{yy}');
        $digits = (int) Stock::setting('claim_invoice_digits', 4);
        $scope = self::query();
        if (str_contains($pattern, '{mm}')) {
            $scope->whereYear('issued_on', $date->year)->whereMonth('issued_on', $date->month);
        } elseif (str_contains($pattern, '{yy}')) {
            $scope->whereYear('issued_on', $date->year);
        }
        $sequence = $scope->count() + 1;
        while (self::where('number', $number = self::formatNumber($pattern, $sequence, $date, $digits))->exists()) {
            $sequence++;
        }

        return $number;
    }

    /** Source ids already on a live claim (a line sent back for resubmission frees its act). */
    private static function claimed(string $type): array
    {
        return ClaimLine::where('source_type', $type)->where('status', '!=', ClaimLine::RESUBMIT)
            ->whereHas('invoice', fn ($q) => $q->where('status', '!=', self::CANCELLED))->pluck('source_id')->all();
    }

    /**
     * Acts of the period whose payer share is not claimed yet, as claim line values plus a grouping key.
     */
    public static function eligible(int $insuranceId, Carbon $from, Carbon $to): Collection
    {
        $policies = PatientInsurance::where('insurance_id', $insuranceId)->pluck('id')->all();
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $lines = collect();
        if (! $policies) {
            return $lines;
        }

        $visits = OpdPatientDepartment::with('doctor.doctorUser')->whereIn('patient_insurance_id', $policies)->where('payer_amount', '>', 0)
            ->whereBetween('appointment_date', [$from, $to])->whereNotIn('id', self::claimed('opd_visit'))->get();
        foreach ($visits as $v) {
            $lines->push(['source_type' => 'opd_visit', 'source_id' => $v->id, 'patient_id' => $v->patient_id, 'patient_insurance_id' => $v->patient_insurance_id,
                'voucher_number' => $v->voucher_number, 'service_date' => Carbon::parse($v->appointment_date)->toDateString(),
                'description' => trim(__('messages.cashier.consultation').' · '.($v->doctor->doctorUser->full_name ?? ''), ' ·'),
                'total' => (float) $v->standard_charge, 'patient_amount' => (float) ($v->patient_amount ?? 0), 'payer_amount' => (float) $v->payer_amount,
                'group' => 'patient:'.$v->patient_id.':'.Carbon::parse($v->appointment_date)->toDateString()]);
        }

        $exams = ExamOrderItem::with('order')->where('payer_amount', '>', 0)->whereNotIn('id', self::claimed('exam_item'))
            ->whereHas('order', fn ($q) => $q->whereIn('patient_insurance_id', $policies)->whereNull('ipd_patient_department_id')->whereBetween('ordered_at', [$from, $to]))->get();
        foreach ($exams as $e) {
            $day = Carbon::parse($e->order->ordered_at)->toDateString();
            $lines->push(['source_type' => 'exam_item', 'source_id' => $e->id, 'patient_id' => $e->order->patient_id, 'patient_insurance_id' => $e->order->patient_insurance_id,
                'voucher_number' => $e->order->voucher_number, 'service_date' => $day, 'description' => (string) $e->description,
                'total' => (float) $e->total, 'patient_amount' => (float) $e->patient_amount, 'payer_amount' => (float) $e->payer_amount,
                'group' => 'patient:'.$e->order->patient_id.':'.$day]);
        }

        // Pharmacy bills once the medicines have been given (paid or dispensed); admission bills go on the admission's claim.
        $bills = MedicineBill::whereIn('patient_insurance_id', $policies)->where('payer_amount', '>', 0)->whereNull('ipd_patient_department_id')
            ->where(fn ($q) => $q->where('payment_status', MedicineBill::FULLPAID)->orWhereNotNull('dispensed_at'))
            ->whereBetween('bill_date', [$from, $to])->whereNotIn('id', self::claimed('medicine_bill'))->get();
        foreach ($bills as $b) {
            $day = Carbon::parse($b->bill_date)->toDateString();
            $lines->push(['source_type' => 'medicine_bill', 'source_id' => $b->id, 'patient_id' => $b->patient_id, 'patient_insurance_id' => $b->patient_insurance_id,
                'voucher_number' => null, 'service_date' => $day, 'description' => __('messages.ipd_bill_extra.pharmacy').' · '.$b->bill_number,
                'total' => (float) $b->net_amount, 'patient_amount' => $b->patientShare(), 'payer_amount' => (float) $b->payer_amount,
                'group' => 'patient:'.$b->patient_id.':'.$day]);
        }

        $ipdBills = IpdBill::with('ipdPatient')->where('payer_amount', '>', 0)->whereBetween('created_at', [$from, $to])
            ->whereNotIn('id', self::claimed('ipd_bill'))->whereHas('ipdPatient', fn ($q) => $q->whereIn('patient_insurance_id', $policies))->get();
        foreach ($ipdBills as $bill) {
            $ipd = $bill->ipdPatient;
            $total = (float) $bill->gross_total + (float) $bill->other_charges;
            $lines->push(['source_type' => 'ipd_bill', 'source_id' => $bill->id, 'patient_id' => $ipd->patient_id, 'patient_insurance_id' => $ipd->patient_insurance_id,
                'voucher_number' => $ipd->voucher_number, 'service_date' => Carbon::parse($ipd->discharge_date ?? $bill->created_at)->toDateString(),
                'description' => __('messages.claims.admission').' · '.$ipd->ipd_number, 'total' => $total,
                'patient_amount' => round(max(0, $total - (float) $bill->reduction_amount - (float) $bill->payer_amount), 2),
                'payer_amount' => (float) $bill->payer_amount, 'group' => 'admission:'.$ipd->id]);
        }

        return $lines->sortBy('service_date')->values();
    }

    /** Make the claim invoices of a period for one insurer; returns them (none when nothing is due). */
    public static function generate(int $insuranceId, Carbon $from, Carbon $to, int $userId, ?string $note = null): Collection
    {
        return DB::transaction(function () use ($insuranceId, $from, $to, $userId, $note) {
            DB::table('insurances')->where('id', $insuranceId)->lockForUpdate()->first(); // one generation at a time per insurer
            $insurance = Insurance::findOrFail($insuranceId);
            $option = in_array($insurance->billing_option, Insurance::BILLING_OPTIONS, true) ? $insurance->billing_option : 'monthly';
            $lines = self::eligible($insuranceId, $from, $to);
            $groups = match ($option) {
                'per_visit' => $lines->map(fn ($line) => collect([$line])),
                'per_admission' => $lines->groupBy('group')->values(),
                default => $lines->isEmpty() ? collect() : collect([$lines]),
            };
            $today = now();

            return $groups->map(function (Collection $group) use ($insurance, $option, $from, $to, $today, $userId, $note) {
                $invoice = self::create(['number' => self::newNumber($today), 'insurance_id' => $insurance->id, 'billing_option' => $option,
                    'period_from' => $from->toDateString(), 'period_to' => $to->toDateString(), 'issued_on' => $today->toDateString(),
                    'due_on' => $insurance->credit_days ? $today->copy()->addDays($insurance->credit_days)->toDateString() : null,
                    'status' => self::ISSUED, 'total_claimed' => round($group->sum('payer_amount'), 2), 'note' => $note, 'created_by' => $userId]);
                foreach ($group as $line) {
                    $invoice->lines()->create(collect($line)->except('group')->all() + ['status' => ClaimLine::PENDING]);
                }

                return $invoice;
            })->values();
        });
    }

    /** Status from the lines: something still owed, all paid, or settled otherwise. */
    public function refreshStatus(): void
    {
        if ($this->status === self::CANCELLED) {
            return;
        }
        $lines = $this->lines()->get();
        $owed = $lines->sum(fn ($line) => $line->outstanding());
        $paid = $lines->sum('paid_amount');
        $status = $owed > 0.004 ? ($paid > 0 ? self::PARTIAL : self::ISSUED) : ($paid >= $this->total_claimed - 0.004 ? self::PAID : self::CLOSED);
        $this->update(['status' => $status]);
    }

    public function cancel(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $fresh = self::lockForUpdate()->findOrFail($this->id);
            if ($fresh->status === self::CANCELLED || $fresh->lines()->where(fn ($q) => $q->where('paid_amount', '>', 0)->orWhere('status', '!=', ClaimLine::PENDING))->exists()) {
                throw new UnprocessableEntityHttpException(__('messages.claims.cannot_cancel'));
            }
            $this->update(['status' => self::CANCELLED, 'cancelled_by' => $userId, 'cancelled_at' => now()]);
        });
    }
}
