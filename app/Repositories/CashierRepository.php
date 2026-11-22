<?php

namespace App\Repositories;

use App\Models\CashSession;
use App\Models\ExamOrder;
use App\Models\MedicineBill;
use App\Models\ExamOrderItem;
use App\Models\FundTransfer;
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
            ->whereHas('order', fn ($q) => $q->where('patient_id', $patientId)->whereNull('ipd_patient_department_id'))->get();
        $paidVisits = $this->paid('opd_visit', $visits->pluck('id')->all());
        $paidExams = $this->paid('exam_item', $exams->pluck('id')->all());
        // Pharmacy bills not tied to an admission (those go on the admission bill).
        $bills = MedicineBill::with('store')->where('patient_id', $patientId)->whereNull('ipd_patient_department_id')
            ->where('payment_status', MedicineBill::UNPAID)->get();
        $paidBills = $this->paid('medicine_bill', $bills->pluck('id')->all());

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
        ]))->concat($bills->map(fn ($b) => [
            'type' => 'medicine_bill', 'id' => $b->id, 'date' => \Illuminate\Support\Carbon::parse($b->bill_date ?? $b->created_at),
            'description' => __('messages.ipd_bill_extra.pharmacy').' · '.$b->bill_number,
            'detail' => $b->store->name ?? '',
            'total' => (float) $b->net_amount, 'payer' => (float) $b->payer_amount,
            'discount' => round(max(0, (float) $b->net_amount - (float) $b->payer_amount - $b->patientShare()), 2),
            'outstanding' => round($b->patientShare() - (float) ($paidBills[$b->id] ?? 0), 2),
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
            ->merge(MedicineBill::where('bill_date', '>=', $since)->whereNull('ipd_patient_department_id')->where('payment_status', MedicineBill::UNPAID)->pluck('patient_id'))
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
            $this->settlePayables($receipt, MedicineBill::FULLPAID);

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
            $this->settlePayables($receipt, MedicineBill::UNPAID);

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

            $this->settlePayables($receipt, MedicineBill::UNPAID);

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

    /**
     * A pharmacy bill paid at the cashier is marked paid, so its medicines leave the store;
     * a voided or refunded receipt makes it unpaid again (the medicines come back unless dispensed).
     */
    private function settlePayables(Receipt $receipt, int $status): void
    {
        $ids = $receipt->lines()->where('payable_type', 'medicine_bill')->pluck('payable_id');
        foreach (MedicineBill::whereIn('id', $ids)->get() as $bill) {
            $bill->update(['payment_status' => $status, 'payment_type' => MedicineBill::MEDICINE_BILL_CASH]);
            $bill->syncStock();
        }
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

    /**
     * Move money out of the session: to another register's open session or to a destination
     * (safe, bank). Never more than the register holds in that payment method.
     */
    public function transfer(CashSession $session, User $user, array $data): FundTransfer
    {
        return DB::transaction(function () use ($session, $user, $data) {
            $session = CashSession::lockForUpdate()->findOrFail($session->id);
            $method = (int) $data['payment_method'];
            $amount = round((float) $data['amount'], 2);
            $available = ($method === self::cashMethod() ? $session->opening_float : 0) + ($session->netByMethod()[$method] ?? 0);
            if ($amount <= 0 || $amount > $available) {
                throw ValidationException::withMessages(['amount' => __('messages.fund_transfer.too_much', ['amount' => formatAmount($available)])]);
            }
            $toSession = null;
            if (! empty($data['to_counter_id'])) {
                if ((int) $data['to_counter_id'] === $session->cash_counter_id) {
                    throw ValidationException::withMessages(['to' => __('messages.fund_transfer.same_register')]);
                }
                $toSession = CashSession::where('cash_counter_id', (int) $data['to_counter_id'])->whereNull('closed_at')->first();
                if (! $toSession) {
                    throw ValidationException::withMessages(['to' => __('messages.fund_transfer.target_closed')]);
                }
            }

            return FundTransfer::create([
                'from_session_id' => $session->id,
                'from_counter_id' => $session->cash_counter_id,
                'to_session_id' => $toSession?->id,
                'to_counter_id' => $toSession?->cash_counter_id,
                'destination' => $toSession ? null : (int) $data['destination'],
                'payment_method' => $method,
                'amount' => $amount,
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public static function sessionRequired(): bool
    {
        return (string) DB::table('settings')->where('key', 'cashier_session_required')->value('value') === '1';
    }

    /** Before taking money on another screen: refuse when the setting requires an open session and there is none. */
    public function assertCanCollect(?User $user): void
    {
        if (self::sessionRequired() && (! $user || ! $this->openSessionFor($user))) {
            throw ValidationException::withMessages(['cashier' => __('messages.cashier.open_first')]);
        }
    }

    /**
     * Issue the receipt for money taken on another screen (IPD payment, advance payment)
     * on the user's open session. Without an open session nothing is issued (unless required).
     */
    public function recordPayment(?User $user, int $patientId, string $type, int $payableId, string $description, float $amount, int $method): ?Receipt
    {
        $session = $user ? $this->openSessionFor($user) : null;
        if (! $session) {
            $this->assertCanCollect($user);

            return null;
        }

        return DB::transaction(function () use ($session, $user, $patientId, $type, $payableId, $description, $amount, $method) {
            $receipt = Receipt::create([
                'receipt_number' => Receipt::nextNumber(),
                'cash_session_id' => $session->id,
                'cash_counter_id' => $session->cash_counter_id,
                'user_id' => $user->id,
                'patient_id' => $patientId,
                'payment_method' => $method,
                'amount' => round($amount, 2),
                'amount_received' => round($amount, 2),
            ]);
            $receipt->lines()->create(['payable_type' => $type, 'payable_id' => $payableId, 'description' => $description,
                'total' => round($amount, 2), 'amount' => round($amount, 2)]);

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
