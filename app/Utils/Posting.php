<?php

namespace App\Utils;

use App\Models\CashCounter;
use App\Models\ClaimInvoice;
use App\Models\ClaimLine;
use App\Models\ClaimPayment;
use App\Models\DoctorStatement;
use App\Models\EmployeePayroll;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\Income;
use App\Models\ItemStock;
use App\Models\JournalEntry;
use App\Models\MasterListItem;
use App\Models\Payment;
use App\Models\PurchaseMedicine;
use App\Models\Receipt;
use App\Models\ReferrerPayout;
use App\Models\Refund;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns the day's activity into journal entries with the accounts and journals chosen by the admin (posting rules).
 * Patients' shares are posted when collected (receipts); payers' shares when the claim invoice is issued.
 * Running it again never posts an event twice; voids, refunds and cancellations post the reversal.
 */
class Posting
{
    // Account keys with no list behind them: key => default account code.
    const ACCOUNTS = [
        'revenue.opd_visit' => '706100', 'revenue.exam_item' => '706300', 'revenue.medicine_bill' => '701', 'revenue.ipd_payment' => '706200',
        'revenue.ipd_bill' => '706200', 'revenue.advance_payment' => '4191', 'revenue.claim_line' => '4111', 'revenue.opening_balance' => '4111',
        'patients' => '4111', 'payers' => '4112', 'suppliers' => '401', 'purchase_paid' => '5711',
        'stock.medicines' => '311', 'stock.items' => '331', 'cost.medicines' => '6031', 'cost.items' => '605', 'stock_adjustment' => '6031',
        'write_off' => '651', 'doctor_fees' => '632', 'withholding' => '447', 'referrer_fees' => '632', 'finance_cash' => '5711', 'payer_payment' => '521',
        'salaries' => '661', 'payroll_deductions' => '447',
    ];

    // Journal per kind of event: key => default journal code.
    const JOURNALS = ['receipts' => 'CA', 'claims' => 'TP', 'claim_payments' => 'BQ', 'purchases' => 'AC', 'stock' => 'OD', 'fees' => 'OD', 'finance' => 'CA', 'transfers' => 'CA', 'payroll' => 'OD'];

    const EVENTS = ['receipt', 'receipt_void', 'receipt_refund', 'claim_invoice', 'claim_cancel', 'claim_payment', 'claim_write_off', 'claim_to_patient',
        'purchase', 'item_stock', 'stock', 'doctor_fees', 'referrer_fees', 'expense', 'income', 'payment', 'fund_transfer', 'payroll'];

    public static function accounts(): array
    {
        return json_decode((string) Stock::setting('posting_accounts', '{}'), true) ?: [];
    }

    public static function journals(): array
    {
        return json_decode((string) Stock::setting('posting_journals', '{}'), true) ?: [];
    }

    /** Every account key the admin can set: fixed ones, then one per payment method, register, income and expense head, transfer destination. */
    public static function accountKeys(): array
    {
        $keys = array_combine(array_keys(self::ACCOUNTS), array_map(fn ($k) => __('messages.posting.keys.'.$k), array_keys(self::ACCOUNTS)));
        foreach (MasterListItem::labels('payment_method') as $code => $label) {
            $keys['method.'.$code] = __('messages.posting.method', ['name' => $label]);
        }
        foreach (CashCounter::orderBy('code')->get() as $counter) {
            $keys['counter.'.$counter->id] = __('messages.posting.counter', ['name' => $counter->code.' '.$counter->name]);
        }
        foreach (MasterListItem::labels('income_head') as $code => $label) {
            $keys['income_head.'.$code] = __('messages.posting.income_head', ['name' => $label]);
        }
        foreach (MasterListItem::labels('expense_head') as $code => $label) {
            $keys['expense_head.'.$code] = __('messages.posting.expense_head', ['name' => $label]);
        }
        foreach (MasterListItem::labels('transfer_destination') as $code => $label) {
            $keys['destination.'.$code] = __('messages.posting.destination', ['name' => $label]);
        }

        return $keys;
    }

    /** Account code used when the admin has not chosen one for a key. */
    public static function defaultCode(string $key): ?string
    {
        if (isset(self::ACCOUNTS[$key])) {
            return self::ACCOUNTS[$key];
        }
        [$group, $code] = explode('.', $key, 2) + [null, null];

        return match ($group) {
            'method' => (int) $code === \App\Repositories\CashierRepository::cashMethod() ? '5711'
                : (preg_match('/mobile|money|orange|moov|wave|momo/i', (string) MasterListItem::label('payment_method', $code, '')) ? '554' : '521'),
            'income_head' => '758',
            'expense_head' => '658',
            'destination' => '521',
            default => null, // a register uses its payment method's account unless set
        };
    }

    /** Account of the money taken in or paid out: the register's own cash account for cash, else the payment method's. */
    private static function money(?int $method, ?int $counterId = null): string
    {
        $map = self::accounts();
        if ($counterId && (int) $method === \App\Repositories\CashierRepository::cashMethod() && ! empty($map['counter.'.$counterId])) {
            return 'counter.'.$counterId;
        }

        return $method ? 'method.'.$method : 'finance_cash';
    }

    /** What should be posted for activity between two dates (the reversal plans point at the entry they undo). */
    public static function plans(Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $plans = collect();
        $plan = fn (string $type, int $id, string $event, $date, string $journal, string $label, ?string $reference, array $lines) => compact('type', 'id', 'event', 'journal', 'label', 'reference', 'lines')
            + ['date' => Carbon::parse($date)->toDateString()];
        $undo = fn (string $type, int $id, string $event, $date, string $undoEvent) => ['type' => $type, 'id' => $id, 'event' => $undoEvent, 'date' => Carbon::parse($date)->toDateString(), 'reverse' => $event];

        foreach (Receipt::with('lines')->whereBetween('created_at', [$from, $to])->get() as $r) {
            $lines = [[self::money($r->payment_method, $r->cash_counter_id), (float) $r->amount, 0]];
            foreach ($r->lines->groupBy('payable_type') as $type => $group) {
                $lines[] = ['revenue.'.$type, 0, (float) $group->sum('amount')];
            }
            $plans->push($plan('receipt', $r->id, 'receipt', $r->created_at, 'receipts', __('messages.posting.labels.receipt', ['number' => $r->receipt_number]), (string) $r->receipt_number, $lines)
                + ['method' => $r->payment_method]);
        }
        foreach (Receipt::whereNotNull('voided_at')->whereBetween('voided_at', [$from, $to])->get() as $r) {
            $plans->push($undo('receipt', $r->id, 'receipt', $r->voided_at, 'receipt_void'));
        }
        foreach (Refund::whereBetween('created_at', [$from, $to])->get() as $refund) {
            $plans->push($undo('receipt', $refund->receipt_id, 'receipt', $refund->created_at, 'receipt_refund'));
        }

        // Invoices brought from before go-live are already in the opening balances: only their payments post.
        foreach (ClaimInvoice::with('lines', 'insurance')->where('is_opening', false)->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])->get() as $c) {
            $lines = [['payers', (float) $c->total_claimed, 0]];
            foreach ($c->lines->groupBy('source_type') as $type => $group) {
                $lines[] = ['revenue.'.$type, 0, (float) $group->sum('payer_amount')];
            }
            $plans->push($plan('claim_invoice', $c->id, 'claim_invoice', $c->issued_on, 'claims', __('messages.posting.labels.claim', ['number' => $c->number, 'payer' => $c->insurance->name ?? '']), $c->number, $lines));
        }
        foreach (ClaimInvoice::where('is_opening', false)->whereNotNull('cancelled_at')->whereBetween('cancelled_at', [$from, $to])->get() as $c) {
            $plans->push($undo('claim_invoice', $c->id, 'claim_invoice', $c->cancelled_at, 'claim_cancel'));
        }
        foreach (ClaimPayment::with('invoice.insurance')->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->get() as $p) {
            $plans->push($plan('claim_payment', $p->id, 'claim_payment', $p->paid_on, 'claim_payments',
                __('messages.posting.labels.claim_payment', ['number' => $p->invoice->number ?? '', 'payer' => $p->invoice->insurance->name ?? '']), $p->reference,
                [[$p->payment_method ? 'method.'.$p->payment_method : 'payer_payment', (float) $p->amount, 0], ['payers', 0, (float) $p->amount]]));
        }
        foreach (ClaimLine::with('invoice')->whereIn('status', [ClaimLine::WRITTEN_OFF, ClaimLine::TO_PATIENT])->whereBetween('settled_on', [$from->toDateString(), $to->toDateString()])->get() as $l) {
            $event = $l->status === ClaimLine::WRITTEN_OFF ? 'claim_write_off' : 'claim_to_patient';
            $plans->push($plan('claim_line', $l->id, $event, $l->settled_on, 'claims', __('messages.posting.labels.'.$event, ['number' => $l->invoice->number ?? '']).' · '.$l->description,
                $l->invoice->number ?? null, [[$event === 'claim_write_off' ? 'write_off' : 'patients', $l->rejectedAmount(), 0], ['payers', 0, $l->rejectedAmount()]]));
        }

        foreach (PurchaseMedicine::with('supplier')->whereBetween('created_at', [$from, $to])->get() as $p) {
            $plans->push($plan('purchase', $p->id, 'purchase', $p->created_at, 'purchases', __('messages.posting.labels.purchase', ['number' => $p->purchase_no, 'supplier' => $p->supplier->name ?? '']),
                $p->purchase_no, [['stock.medicines', (float) $p->net_amount, 0], [$p->payment_status ? 'purchase_paid' : 'suppliers', 0, (float) $p->net_amount]]));
        }
        foreach (ItemStock::whereBetween('created_at', [$from, $to])->get() as $s) {
            $amount = round((float) $s->quantity * (float) $s->purchase_price, 2);
            $plans->push($plan('item_stock', $s->id, 'item_stock', $s->created_at, 'purchases', __('messages.posting.labels.item_stock', ['id' => $s->id]), null,
                [['stock.items', $amount, 0], ['suppliers', 0, $amount]]));
        }

        // Stock leaving or corrected, one entry per finished day (today is posted tomorrow).
        $day = $from->copy()->startOfDay();
        $lastDay = min($to->copy()->startOfDay()->getTimestamp(), now()->subDay()->startOfDay()->getTimestamp());
        while ($day->getTimestamp() <= $lastDay) {
            if ($lines = self::stockLines($day)) {
                $plans->push($plan('stock_day', (int) $day->format('Ymd'), 'stock', $day, 'stock', __('messages.posting.labels.stock', ['date' => $day->format('d/m/Y')]), null, $lines));
            }
            $day->addDay();
        }

        foreach (DoctorStatement::with('doctor.doctorUser')->where('status', DoctorStatement::PAID)->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->get() as $s) {
            $lines = [['doctor_fees', (float) $s->gross, 0], [self::money($s->payment_method), 0, (float) $s->net]];
            if ($s->withholding > 0) {
                $lines[] = ['withholding', 0, (float) $s->withholding];
            }
            $plans->push($plan('doctor_statement', $s->id, 'doctor_fees', $s->paid_on, 'fees', __('messages.posting.labels.doctor_fees', ['number' => $s->number, 'name' => $s->doctor->doctorUser->full_name ?? '']),
                $s->payment_reference ?: $s->number, $lines));
        }
        foreach (ReferrerPayout::whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->get() as $p) {
            $plans->push($plan('referrer_payout', $p->id, 'referrer_fees', $p->paid_on, 'fees',
                __('messages.posting.labels.referrer_fees', ['name' => MasterListItem::label('referrer', $p->referrer_code, '')]), $p->reference,
                [['referrer_fees', (float) $p->amount, 0], [self::money($p->payment_method), 0, (float) $p->amount]]));
        }

        // Salaries marked paid: gross to salaries, net paid out of the finance cash, deductions owed.
        foreach (EmployeePayroll::where('status', EmployeePayroll::PAID)->whereBetween('updated_at', [$from, $to])->get() as $p) {
            $net = (float) $p->net_salary;
            $deductions = max(0, (float) $p->deductions);
            $lines = [['salaries', $net + $deductions, 0], ['finance_cash', 0, $net]];
            if ($deductions > 0) {
                $lines[] = ['payroll_deductions', 0, $deductions];
            }
            $plans->push($plan('payroll', $p->id, 'payroll', $p->updated_at, 'payroll', __('messages.posting.labels.payroll', ['month' => $p->month, 'year' => $p->year,
                'number' => $p->payroll_id]), (string) $p->payroll_id, $lines));
        }

        foreach (Expense::whereBetween('date', [$from, $to])->get() as $e) {
            $plans->push($plan('expense', $e->id, 'expense', $e->date, 'finance', $e->name, $e->invoice_number,
                [['expense_head.'.$e->expense_head, (float) $e->amount, 0], ['finance_cash', 0, (float) $e->amount]]));
        }
        foreach (Income::whereBetween('date', [$from, $to])->get() as $i) {
            $plans->push($plan('income', $i->id, 'income', $i->date, 'finance', $i->name, $i->invoice_number,
                [['finance_cash', (float) $i->amount, 0], ['income_head.'.$i->income_head, 0, (float) $i->amount]]));
        }
        foreach (Payment::whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])->get() as $p) {
            $plans->push($plan('payment', $p->id, 'payment', $p->payment_date, 'finance', __('messages.posting.labels.payment', ['name' => $p->pay_to]), null,
                [[['id' => (int) $p->account_id], (float) $p->amount, 0], ['finance_cash', 0, (float) $p->amount]]));
        }
        foreach (FundTransfer::whereBetween('created_at', [$from, $to])->get() as $t) {
            $target = $t->to_counter_id ? self::money($t->payment_method, $t->to_counter_id) : 'destination.'.$t->destination;
            $plans->push($plan('fund_transfer', $t->id, 'fund_transfer', $t->created_at, 'transfers', __('messages.posting.labels.transfer'), null,
                [[$target, (float) $t->amount, 0], [self::money($t->payment_method, $t->from_counter_id), 0, (float) $t->amount]]));
        }

        return $plans->sortBy('date')->values();
    }

    /** Lines of one day's stock entry: cost of what left, count corrections, returns to suppliers, undone receipts. */
    public static function stockLines(Carbon $day): array
    {
        $moves = StockMovement::whereDate('created_at', $day->toDateString())->whereIn('movement_type', [Stock::SALE, Stock::RETURN, Stock::ISSUE, Stock::ADJUSTMENT, Stock::SUPPLIER_RETURN, Stock::REVERSAL])->get();
        $sums = [];
        foreach ($moves as $m) {
            $kind = $m->stockable_type === \App\Models\Item::class ? 'items' : 'medicines';
            $value = round($m->quantity * $m->unit_cost, 2); // + into stock, - out of stock
            $counter = match (true) {
                $m->movement_type === Stock::ADJUSTMENT => 'stock_adjustment',
                $m->movement_type === Stock::SUPPLIER_RETURN, $m->movement_type === Stock::REVERSAL && in_array($m->document_type, [PurchaseMedicine::class, ItemStock::class], true) => 'suppliers',
                $m->document_type === \App\Models\StockTransfer::class => null, // between stores: same account
                default => 'cost.'.$kind,
            };
            if ($counter) {
                $sums[$kind.'|'.$counter] = ($sums[$kind.'|'.$counter] ?? 0) + $value;
            }
        }
        $lines = [];
        foreach ($sums as $key => $value) {
            [$kind, $counter] = explode('|', $key);
            $value = round($value, 2);
            if (abs($value) < 0.005) {
                continue;
            }
            $lines[] = $value > 0 ? ['stock.'.$kind, $value, 0] : ['stock.'.$kind, 0, -$value];
            $lines[] = $value > 0 ? [$counter, 0, $value] : [$counter, -$value, 0];
        }

        return $lines;
    }

    private static function liveEntry(string $type, int $id, string $event): ?JournalEntry
    {
        return JournalEntry::where('source_type', $type)->where('source_id', $id)->where('source_event', $event)->whereNull('reversal_of')
            ->whereNotExists(fn ($q) => $q->from('journal_entries as r')->whereColumn('r.reversal_of', 'journal_entries.id'))->first();
    }

    /**
     * Post what is missing between two dates.
     *
     * @return array{posted: int, skipped: int, errors: string[]}
     */
    public static function generate(Carbon $from, Carbon $to, ?int $userId = null): array
    {
        $result = ['posted' => 0, 'skipped' => 0, 'errors' => []];
        $accounts = self::accounts();
        $journals = self::journals();
        $codes = \App\Models\Account::pluck('id', 'code');
        $journalCodes = \App\Models\Journal::pluck('id', 'code');
        foreach (self::plans($from, $to) as $p) {
            try {
                if (isset($p['reverse'])) {
                    $original = self::liveEntry($p['type'], $p['id'], $p['reverse']);
                    if (! $original || JournalEntry::where('source_type', $p['type'])->where('source_id', $p['id'])->where('source_event', $p['event'])->exists()) {
                        $result['skipped']++;

                        continue;
                    }
                    $reversal = Ledger::reverse($original->load('lines', 'journal'), $p['date'], $userId);
                    $reversal->update(['source_event' => $p['event']]);
                    $result['posted']++;

                    continue;
                }
                if (JournalEntry::where('source_type', $p['type'])->where('source_id', $p['id'])->where('source_event', $p['event'])->exists()) {
                    $result['skipped']++;

                    continue;
                }
                $lines = [];
                foreach ($p['lines'] as [$key, $debit, $credit]) {
                    $id = is_array($key) ? $key['id'] : ($accounts[$key] ?? ($codes[self::defaultCode($key)] ?? null));
                    if (! $id) {
                        throw new \RuntimeException(__('messages.posting.missing_account', ['key' => is_array($key) ? '#' : (self::accountKeys()[$key] ?? $key)]));
                    }
                    $lines[] = ['account_id' => $id, 'debit' => $debit, 'credit' => $credit];
                }
                $journalId = (isset($p['method']) ? ($journals['method.'.$p['method']] ?? null) : null) ?? $journals[$p['journal']] ?? $journalCodes[self::JOURNALS[$p['journal']]] ?? null;
                $journal = \App\Models\Journal::find($journalId);
                Ledger::post($journal ?? self::JOURNALS[$p['journal']], $p['date'], $p['label'], $lines, $p['reference'],
                    ['type' => $p['type'], 'id' => $p['id'], 'event' => $p['event']], $userId);
                $result['posted']++;
            } catch (\Throwable $e) {
                $result['errors'][] = $p['date'].' · '.$p['label'].' : '.$e->getMessage();
            }
        }

        return $result;
    }
}
