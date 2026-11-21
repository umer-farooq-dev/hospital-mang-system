<?php

namespace App\Repositories;

use App\Models\CashSession;
use App\Models\OpdPatientDepartment;
use App\Models\Patient;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cashier: sessions on the user's register, collecting the patient's share, closing.
 */
class CashierRepository
{
    public function openSessionFor(User $user): ?CashSession
    {
        return $user->cash_counter_id ? CashSession::where('cash_counter_id', $user->cash_counter_id)->whereNull('closed_at')->latest('id')->first() : null;
    }

    public function open(User $user, float $float): CashSession
    {
        if (! $user->cash_counter_id) {
            throw ValidationException::withMessages(['counter' => __('messages.cashier.no_counter')]);
        }

        return DB::transaction(function () use ($user, $float) {
            $existing = CashSession::where('cash_counter_id', $user->cash_counter_id)->whereNull('closed_at')->lockForUpdate()->first();

            return $existing ?? CashSession::create(['cash_counter_id' => $user->cash_counter_id, 'user_id' => $user->id,
                'opened_at' => now(), 'opening_float' => max(0, $float)]);
        });
    }

    /** Amount already paid per OPD visit (valid receipts only). */
    private function paidPerVisit(array $visitIds): Collection
    {
        return ReceiptLine::where('payable_type', 'opd_visit')->whereIn('payable_id', $visitIds)
            ->whereHas('receipt', fn ($q) => $q->whereNull('voided_at'))
            ->selectRaw('payable_id, SUM(amount) as paid')->groupBy('payable_id')->pluck('paid', 'payable_id');
    }

    /** The patient's visits with a share still to pay, each with its outstanding amount. */
    public function unpaidVisits(int $patientId): Collection
    {
        $visits = OpdPatientDepartment::with('doctor.doctorUser', 'doctor.department')
            ->where('patient_id', $patientId)->whereNotNull('patient_amount')->where('patient_amount', '>', 0)
            ->orderBy('appointment_date')->get();
        $paid = $this->paidPerVisit($visits->pluck('id')->all());

        return $visits->each(fn ($v) => $v->outstanding = round($v->patient_amount - (float) ($paid[$v->id] ?? 0), 2))
            ->filter(fn ($v) => $v->outstanding > 0)->values();
    }

    /** Patients with something to pay, for the cashier's list. */
    public function patientsToCollect(): array
    {
        $visits = OpdPatientDepartment::whereNotNull('patient_amount')->where('patient_amount', '>', 0)
            ->whereDate('appointment_date', '>=', now()->subDays(90))->get(['id', 'patient_id', 'patient_amount']);
        $paid = $this->paidPerVisit($visits->pluck('id')->all());
        $patientIds = $visits->filter(fn ($v) => $v->patient_amount - (float) ($paid[$v->id] ?? 0) > 0)->pluck('patient_id')->unique();

        return Patient::with('patientUser')->whereIn('id', $patientIds)->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->patientUser->full_name.' · '.$p->patient_unique_id])->all();
    }

    /**
     * Collect the full outstanding share of the chosen visits on one receipt.
     * ponytail: the outstanding amount is always paid in full; add partial payments if the client needs them.
     */
    public function collect(CashSession $session, User $user, int $patientId, array $visitIds, array $payment): Receipt
    {
        return DB::transaction(function () use ($session, $user, $patientId, $visitIds, $payment) {
            $visits = $this->unpaidVisits($patientId)->whereIn('id', array_map('intval', $visitIds));
            if ($visits->isEmpty()) {
                throw ValidationException::withMessages(['visits' => __('messages.cashier.nothing_selected')]);
            }
            $total = round($visits->sum('outstanding'), 2);
            $received = isset($payment['amount_received']) && $payment['amount_received'] !== '' ? (float) $payment['amount_received'] : $total;
            if ($received < $total) {
                throw ValidationException::withMessages(['amount_received' => __('messages.cashier.received_too_low')]);
            }

            $receipt = Receipt::create([
                'receipt_number' => Receipt::nextNumber(),
                'cash_session_id' => $session->id,
                'cash_counter_id' => $session->cash_counter_id,
                'user_id' => $user->id,
                'patient_id' => $patientId,
                'payment_method' => (int) $payment['payment_method'],
                'amount' => $total,
                'amount_received' => $received,
                'change_given' => round($received - $total, 2),
                'cheque_number' => $payment['cheque_number'] ?? null,
                'cheque_drawer' => $payment['cheque_drawer'] ?? null,
            ]);
            foreach ($visits as $visit) {
                $receipt->lines()->create([
                    'payable_type' => 'opd_visit',
                    'payable_id' => $visit->id,
                    'description' => __('messages.cashier.consultation').' · '.($visit->doctor->doctorUser->full_name ?? ''),
                    'total' => $visit->standard_charge,
                    'discount_amount' => $visit->discount_amount,
                    'payer_amount' => $visit->payer_amount,
                    'amount' => $visit->outstanding,
                ]);
            }

            return $receipt;
        });
    }

    /** The payment method counted as cash in the drawer (Settings), Espèces by default. */
    public static function cashMethod(): int
    {
        return (int) (Setting::where('key', 'cash_payment_method')->value('value') ?: 1);
    }

    /** Close the session: expected cash, counted cash and the next closing number (N° Arrêt). */
    public function close(CashSession $session, float $counted, ?string $notes): CashSession
    {
        return DB::transaction(function () use ($session, $counted, $notes) {
            $session = CashSession::lockForUpdate()->findOrFail($session->id);
            if ($session->closed_at) {
                return $session;
            }
            $cash = $session->totalsByMethod()[self::cashMethod()] ?? 0;
            $session->update([
                'closed_at' => now(),
                'closing_number' => (int) CashSession::lockForUpdate()->max('closing_number') + 1,
                'expected_cash' => round($session->opening_float + $cash, 2),
                'counted_cash' => round($counted, 2),
                'notes' => $notes,
            ]);

            return $session;
        });
    }
}
