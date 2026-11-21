<?php

namespace App\Repositories;

use App\Models\CashSession;
use App\Models\ExamOrder;
use App\Models\ExamOrderItem;
use App\Models\OpdPatientDepartment;
use App\Models\Patient;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\Refund;
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

    /** Amount already paid per item of one type (valid receipts only, not refunded). */
    private function paid(string $type, array $ids): Collection
    {
        return ReceiptLine::where('payable_type', $type)->whereIn('payable_id', $ids)
            ->whereHas('receipt', fn ($q) => $q->whereNull('voided_at')->whereDoesntHave('refund'))
            ->selectRaw('payable_id, SUM(amount) as paid')->groupBy('payable_id')->pluck('paid', 'payable_id');
    }

    /**
     * Everything the patient still has to pay: OPD visits and requested exams, oldest first.
     * Each item: key ("opd_visit:12"), type, id, date, description, detail, total, discount, payer, outstanding.
     */
    public function unpaidItems(int $patientId): Collection
    {
        $visits = OpdPatientDepartment::with('doctor.doctorUser', 'doctor.department')
            ->where('patient_id', $patientId)->whereNotNull('patient_amount')->where('patient_amount', '>', 0)->get();
        $exams = ExamOrderItem::with('order.doctor.doctorUser')->where('patient_amount', '>', 0)
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patientId))->get();
        $paidVisits = $this->paid('opd_visit', $visits->pluck('id')->all());
        $paidExams = $this->paid('exam_item', $exams->pluck('id')->all());

        $items = $visits->map(fn ($v) => [
            'type' => 'opd_visit', 'id' => $v->id, 'date' => $v->appointment_date,
            'description' => __('messages.cashier.consultation').' · '.($v->doctor->doctorUser->full_name ?? ''),
            'detail' => trim(($v->doctor->department->title ?? '').' · '.$v->opd_number, ' ·'),
            'total' => (float) $v->standard_charge, 'discount' => (float) $v->discount_amount, 'payer' => (float) $v->payer_amount,
            'outstanding' => round($v->patient_amount - (float) ($paidVisits[$v->id] ?? 0), 2),
        ])->concat($exams->map(fn ($e) => [
            'type' => 'exam_item', 'id' => $e->id, 'date' => $e->order->ordered_at,
            'description' => $e->description,
            'detail' => __('messages.exam_orders.exam').($e->order->doctor ? ' · '.$e->order->doctor->doctorUser->full_name : ''),
            'total' => $e->total, 'discount' => $e->discount_amount, 'payer' => $e->payer_amount,
            'outstanding' => round($e->patient_amount - (float) ($paidExams[$e->id] ?? 0), 2),
        ]));

        return $items->filter(fn ($i) => $i['outstanding'] > 0)
            ->map(fn ($i) => $i + ['key' => $i['type'].':'.$i['id']])
            ->sortBy('date')->values();
    }

    /**
     * Patients with something to pay in the last 90 days, for the cashier's list.
     * ponytail: checks each candidate patient one by one; fine for a day's patients, add a summary query if it gets slow.
     */
    public function patientsToCollect(): array
    {
        $since = now()->subDays(90);
        $candidates = OpdPatientDepartment::where('patient_amount', '>', 0)->where('appointment_date', '>=', $since)->pluck('patient_id')
            ->merge(ExamOrder::where('ordered_at', '>=', $since)->whereHas('items', fn ($q) => $q->where('patient_amount', '>', 0))->pluck('patient_id'))
            ->unique();
        $patientIds = $candidates->filter(fn ($id) => $this->unpaidItems((int) $id)->isNotEmpty());

        return Patient::with('patientUser')->whereIn('id', $patientIds)->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->patientUser->full_name.' · '.$p->patient_unique_id])->all();
    }

    /**
     * Collect the full outstanding share of the chosen items on one receipt.
     * ponytail: the outstanding amount is always paid in full; add partial payments if the client needs them.
     *
     * @param  string[]  $keys  item keys from unpaidItems(), e.g. "opd_visit:12", "exam_item:4"
     */
    public function collect(CashSession $session, User $user, int $patientId, array $keys, array $payment): Receipt
    {
        return DB::transaction(function () use ($session, $user, $patientId, $keys, $payment) {
            $items = $this->unpaidItems($patientId)->whereIn('key', $keys);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('messages.cashier.nothing_selected')]);
            }
            $total = round($items->sum('outstanding'), 2);
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
            foreach ($items as $item) {
                $receipt->lines()->create([
                    'payable_type' => $item['type'],
                    'payable_id' => $item['id'],
                    'description' => $item['description'],
                    'total' => $item['total'],
                    'discount_amount' => $item['discount'],
                    'payer_amount' => $item['payer'],
                    'amount' => $item['outstanding'],
                ]);
            }

            return $receipt;
        });
    }

    /** A receipt can be corrected only while its session is open and nothing has undone it. */
    private function assertCorrectable(Receipt $receipt): void
    {
        if ($receipt->status !== 'valid') {
            throw ValidationException::withMessages(['receipt' => __('messages.receipts.already_'.$receipt->status)]);
        }
        if ($receipt->session->closed_at) {
            throw ValidationException::withMessages(['receipt' => __('messages.receipts.session_closed')]);
        }
    }

    /** Cancel a wrong receipt (same open session); its items become payable again. */
    public function void(Receipt $receipt, User $user, int $reason): Receipt
    {
        return DB::transaction(function () use ($receipt, $user, $reason) {
            $receipt = Receipt::with('session', 'refund')->lockForUpdate()->findOrFail($receipt->id);
            $this->assertCorrectable($receipt);
            $receipt->update(['voided_at' => now(), 'void_reason' => $reason, 'voided_by' => $user->id]);

            return $receipt;
        });
    }

    /** Give the money back, out of the refunding user's open session, with the same method. */
    public function refund(Receipt $receipt, User $user, int $reason, ?string $notes): Refund
    {
        return DB::transaction(function () use ($receipt, $user, $reason, $notes) {
            $receipt = Receipt::with('refund')->lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->status !== 'valid') {
                throw ValidationException::withMessages(['receipt' => __('messages.receipts.already_'.$receipt->status)]);
            }
            $session = $this->openSessionFor($user);
            if (! $session) {
                throw ValidationException::withMessages(['receipt' => __('messages.cashier.open_first')]);
            }

            return Refund::create([
                'receipt_id' => $receipt->id,
                'cash_session_id' => $session->id,
                'cash_counter_id' => $session->cash_counter_id,
                'user_id' => $user->id,
                'reason' => $reason,
                'payment_method' => $receipt->payment_method,
                'amount' => $receipt->amount,
                'notes' => $notes,
            ]);
        });
    }

    /** Correct the payment method of a receipt of a session still open. */
    public function changeMethod(Receipt $receipt, int $method): Receipt
    {
        return DB::transaction(function () use ($receipt, $method) {
            $receipt = Receipt::with('session', 'refund')->lockForUpdate()->findOrFail($receipt->id);
            $this->assertCorrectable($receipt);
            $receipt->update(['payment_method' => $method]);

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
            $cash = $session->netByMethod()[self::cashMethod()] ?? 0;
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
