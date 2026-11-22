<?php

namespace App\Models;

use App\Utils\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * What a doctor earned in a period (old "Revenus des médecins"): acts by the doctor's pay plan, operation fees,
 * other income, minus the withholding taxes set by the admin. draft -> validated -> paid, or cancelled.
 */
class DoctorStatement extends Model
{
    const DRAFT = 'draft';

    const VALIDATED = 'validated';

    const PAID = 'paid';

    const CANCELLED = 'cancelled';

    const STATUSES = [self::DRAFT, self::VALIDATED, self::PAID, self::CANCELLED];

    protected $fillable = ['number', 'doctor_id', 'period_from', 'period_to', 'pay_type', 'hours', 'gross', 'withholding', 'net', 'status',
        'paid_on', 'payment_method', 'payment_reference', 'note', 'created_by', 'validated_by', 'validated_at'];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'paid_on' => 'date', 'validated_at' => 'datetime',
        'hours' => 'float', 'gross' => 'float', 'withholding' => 'float', 'net' => 'float'];

    public function lines(): HasMany
    {
        return $this->hasMany(DoctorStatementLine::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /** @return array<int, array{name: string, percent: float}> */
    public static function withholdings(): array
    {
        $rows = json_decode((string) Stock::setting('doctor_withholdings', '[]'), true) ?: [];

        return array_values(array_filter(array_map(fn ($r) => ['name' => trim((string) ($r['name'] ?? '')), 'percent' => (float) ($r['percent'] ?? 0)], $rows),
            fn ($r) => $r['name'] !== '' && $r['percent'] > 0));
    }

    /**
     * The lines of a doctor's statement for a period.
     *
     * @return array<int, array> line values (kind, source, date, description, base, percent, amount)
     */
    public static function compute(Doctor $doctor, Carbon $from, Carbon $to, ?float $hours = null, float $other = 0, ?string $otherNote = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $plan = DoctorFeePlan::forDoctor($doctor->id);
        $default = PriceList::defaultList();
        $lists = PriceList::all()->keyBy('id');
        $lines = [];
        $line = fn ($kind, $type, $id, $date, $description, $base, $percent, $amount) => ['kind' => $kind, 'source_type' => $type, 'source_id' => $id,
            'line_date' => $date ? Carbon::parse($date)->toDateString() : null, 'description' => $description, 'base_amount' => round((float) $base, 2),
            'percent' => $percent, 'amount' => round((float) $amount, 2)];

        $visits = in_array($plan->pay_type, ['share', 'per_patient'], true)
            ? OpdPatientDepartment::with('patient.patientUser')->where('doctor_id', $doctor->id)->whereBetween('appointment_date', [$from, $to])->orderBy('appointment_date')->get() : collect();
        $charge = DoctorOPDCharge::where('doctor_id', $doctor->id)->first();
        foreach ($visits as $v) {
            $label = __('messages.cashier.consultation').' · '.($v->patient->patientUser->full_name ?? '');
            if ($plan->pay_type === 'per_patient') {
                $lines[] = $line('consultation', 'opd_visit', $v->id, $v->appointment_date, $label, $v->standard_charge, null, $plan->rate);
            } else {
                $list = $lists[$v->price_list_id] ?? $default;
                $percent = $charge && $list ? ItemPrice::doctorShare($charge, $list) : 0.0;
                $lines[] = $line('consultation', 'opd_visit', $v->id, $v->appointment_date, $label, $v->standard_charge, $percent, $v->standard_charge * $percent / 100);
            }
        }

        // Exams the doctor performed (validated the result or the report).
        $exams = in_array($plan->pay_type, ['share', 'per_exam'], true) && $doctor->user_id
            ? ExamOrderItem::with('order')->where('validated_by', $doctor->user_id)->whereBetween('validated_at', [$from, $to])->orderBy('validated_at')->get() : collect();
        foreach ($exams as $e) {
            if ($plan->pay_type === 'per_exam') {
                $lines[] = $line('exam', 'exam_item', $e->id, $e->validated_at, $e->description, $e->total, null, $plan->rate);
            } else {
                $list = $lists[$e->order->price_list_id ?? 0] ?? $default;
                $test = $e->testable();
                $percent = $test && $list ? ItemPrice::doctorShare($test, $list) : 0.0;
                $lines[] = $line('exam', 'exam_item', $e->id, $e->validated_at, $e->description, $e->total, $percent, $e->total * $percent / 100);
            }
        }

        if ($plan->pay_type === 'monthly') {
            $months = (int) $from->copy()->startOfMonth()->diffInMonths($to->copy()->startOfMonth()) + 1;
            $lines[] = $line('fixed', null, null, null, __('messages.doctor_fees.monthly_line', ['months' => $months]), $plan->rate, null, $plan->rate * $months);
        }
        if ($plan->pay_type === 'hourly' && $hours) {
            $lines[] = $line('hours', null, null, null, __('messages.doctor_fees.hours_line', ['hours' => $hours]), $plan->rate, null, $plan->rate * $hours);
        }

        // Operation fees (K tariff), whatever the pay plan.
        $roles = ['doctor_id' => 'surgeon_fee', 'assistant_doctor_id' => 'assistant_fee', 'anaesthetist_doctor_id' => 'anaesthetist_fee'];
        $operations = IpdOperation::with('operations')->whereBetween('operation_date', [$from->toDateString(), $to->toDateString()])
            ->where(fn ($q) => $q->where('doctor_id', $doctor->id)->orWhere('assistant_doctor_id', $doctor->id)->orWhere('anaesthetist_doctor_id', $doctor->id))->get();
        foreach ($operations as $op) {
            if (! $op->isBillable()) {
                continue;
            }
            foreach ($roles as $column => $fee) {
                if ((int) $op->{$column} === (int) $doctor->id && (float) $op->{$fee} > 0) {
                    $lines[] = $line('operation', 'operation', $op->id, $op->operation_date, ($op->operations->name ?? __('messages.doctor_fees.operation')).' · '.__('messages.doctor_fees.roles.'.$fee),
                        $op->{$fee}, null, $op->{$fee});
                }
            }
        }

        if ($other > 0) {
            $lines[] = $line('other', null, null, null, $otherNote ?: __('messages.doctor_fees.other'), $other, null, $other);
        }

        $gross = round(array_sum(array_column($lines, 'amount')), 2);
        if (! $plan->withholding_exempt && $gross > 0) {
            foreach (self::withholdings() as $w) {
                $lines[] = $line('withholding', null, null, null, $w['name'], $gross, $w['percent'], -round($gross * $w['percent'] / 100, 2));
            }
        }

        return $lines;
    }

    public static function newNumber(): string
    {
        $prefix = trim((string) Stock::setting('doctor_statement_prefix', 'HD')) ?: 'HD';
        $year = (int) now()->format('Y');
        $last = self::where('number', 'like', $prefix.'-'.$year.'-%')->orderByDesc('id')->value('number');

        return Certificate::nextNumber($prefix, $year, $last);
    }

    /** A doctor is paid once for a day: no two live statements may overlap. */
    public static function overlaps(int $doctorId, Carbon $from, Carbon $to, ?int $ignoreId = null): bool
    {
        return self::where('doctor_id', $doctorId)->where('status', '!=', self::CANCELLED)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('period_from', '<=', $to->toDateString())->whereDate('period_to', '>=', $from->toDateString())->exists();
    }

    public static function open(Doctor $doctor, Carbon $from, Carbon $to, ?float $hours, float $other, ?string $note, int $userId): self
    {
        return DB::transaction(function () use ($doctor, $from, $to, $hours, $other, $note, $userId) {
            DB::table('doctors')->where('id', $doctor->id)->lockForUpdate()->first();
            if (self::overlaps($doctor->id, $from, $to)) {
                throw new UnprocessableEntityHttpException(__('messages.doctor_fees.overlap'));
            }
            $statement = self::create(['number' => self::newNumber(), 'doctor_id' => $doctor->id, 'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(), 'pay_type' => DoctorFeePlan::forDoctor($doctor->id)->pay_type, 'hours' => $hours,
                'status' => self::DRAFT, 'note' => $note, 'created_by' => $userId]);
            $statement->fillLines(self::compute($doctor, $from, $to, $hours, $other, $note));

            return $statement;
        });
    }

    /** Recompute a draft (acts recorded since, plan or rates changed). Other income is kept. */
    public function recompute(): void
    {
        DB::transaction(function () {
            $this->claim([self::DRAFT]);
            $other = $this->lines()->where('kind', 'other')->first();
            $this->lines()->delete();
            $this->update(['pay_type' => DoctorFeePlan::forDoctor($this->doctor_id)->pay_type]);
            $this->fillLines(self::compute($this->doctor, $this->period_from, $this->period_to, $this->hours, (float) ($other->amount ?? 0), $other->description ?? null));
        });
    }

    private function fillLines(array $lines): void
    {
        foreach ($lines as $line) {
            $this->lines()->create($line);
        }
        $gross = round(collect($lines)->where('kind', '!=', 'withholding')->sum('amount'), 2);
        $withheld = round(-collect($lines)->where('kind', 'withholding')->sum('amount'), 2);
        $this->update(['gross' => $gross, 'withholding' => $withheld, 'net' => round($gross - $withheld, 2)]);
    }

    private function claim(array $from): void
    {
        $fresh = self::lockForUpdate()->findOrFail($this->id);
        if (! in_array($fresh->status, $from, true)) {
            throw new UnprocessableEntityHttpException(__('messages.doctor_fees.wrong_status'));
        }
    }

    public function validateStatement(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::DRAFT]);
            $this->update(['status' => self::VALIDATED, 'validated_by' => $userId, 'validated_at' => now()]);
        });
    }

    public function markPaid(string $paidOn, ?int $method, ?string $reference): void
    {
        DB::transaction(function () use ($paidOn, $method, $reference) {
            $this->claim([self::VALIDATED]);
            $this->update(['status' => self::PAID, 'paid_on' => $paidOn, 'payment_method' => $method, 'payment_reference' => $reference]);
        });
    }

    public function cancel(): void
    {
        DB::transaction(function () {
            $this->claim([self::DRAFT, self::VALIDATED]);
            $this->update(['status' => self::CANCELLED]);
        });
    }
}
