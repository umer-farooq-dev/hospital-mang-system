<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalLine;
use App\Utils\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** General ledger and trial balance. */
class AccountingReportController extends AppBaseController
{
    private function period(Request $request): array
    {
        return [Carbon::parse($request->input('from') ?: now()->startOfYear())->toDateString(), Carbon::parse($request->input('to') ?: now())->toDateString()];
    }

    /** Lines of one account with the running balance (debit minus credit), opening balance first. */
    public function ledger(Request $request)
    {
        [$from, $to] = $this->period($request);
        $accounts = JournalEntryController::postableAccounts();
        $account = Account::find($request->integer('account_id'));
        $lines = collect();
        $opening = 0.0;
        if ($account) {
            $before = Ledger::totals(null, Carbon::parse($from)->subDay()->toDateString(), $account->id)[$account->id] ?? ['debit' => 0, 'credit' => 0];
            $opening = round($before['debit'] - $before['credit'], 2);
            $lines = JournalLine::with('entry.journal')->where('account_id', $account->id)
                ->whereHas('entry', fn ($q) => $q->whereBetween('entry_date', [$from, $to]))->get()
                ->sortBy(fn ($l) => $l->entry->entry_date->format('Ymd').str_pad((string) $l->journal_entry_id, 10, '0', STR_PAD_LEFT))->values();
        }

        return view('accounting.ledger', compact('accounts', 'account', 'lines', 'opening', 'from', 'to'));
    }

    /** Debit and credit totals and balances of every account for the period; the totals must match. */
    public function trialBalance(Request $request)
    {
        [$from, $to] = $this->period($request);
        $totals = Ledger::totals($from, $to);
        $rows = Account::whereIn('id', array_keys($totals))->orderBy('code')->get()->map(function ($a) use ($totals) {
            $t = $totals[$a->id];
            $balance = round($t['debit'] - $t['credit'], 2);

            return ['account' => $a, 'debit' => $t['debit'], 'credit' => $t['credit'], 'balance_debit' => max(0, $balance), 'balance_credit' => max(0, -$balance)];
        });

        return view('accounting.trial_balance', compact('rows', 'from', 'to'));
    }
    /** Income and expenses of the period, by account, and the result. */
    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->period($request);
        $totals = Ledger::totals($from, $to);
        $accounts = Account::whereIn('id', array_keys($totals))->whereIn('account_class', ['income', 'expense'])->orderBy('code')->get();
        $income = $accounts->where('account_class', 'income')->map(fn ($a) => ['account' => $a, 'amount' => round($totals[$a->id]['credit'] - $totals[$a->id]['debit'], 2)])->values();
        $expense = $accounts->where('account_class', 'expense')->map(fn ($a) => ['account' => $a, 'amount' => round($totals[$a->id]['debit'] - $totals[$a->id]['credit'], 2)])->values();

        return view('accounting.profit_loss', compact('income', 'expense', 'from', 'to'));
    }

    /** Assets against liabilities and equity at a date, with the result of the year. */
    public function balanceSheet(Request $request)
    {
        $at = Carbon::parse($request->input('at') ?: now())->toDateString();
        $year = \App\Models\FiscalYear::whereDate('starts_on', '<=', $at)->whereDate('ends_on', '>=', $at)->first();
        $yearStart = $year?->starts_on?->toDateString();
        $totals = Ledger::totals(null, $at);
        $accounts = Account::whereIn('id', array_keys($totals))->orderBy('code')->get();
        $side = fn ($class, $sign) => $accounts->where('account_class', $class)->map(fn ($a) => ['account' => $a, 'amount' => round($sign * ($totals[$a->id]['debit'] - $totals[$a->id]['credit']), 2)])
            ->filter(fn ($r) => abs($r['amount']) >= 0.005)->values();
        $result = function (?string $from, string $to) {
            $t = Ledger::totals($from, $to);
            $classes = Account::whereIn('id', array_keys($t))->pluck('account_class', 'id');

            $sum = 0.0;
            foreach ($t as $id => $v) {
                if (in_array($classes[$id] ?? null, ['income', 'expense'], true)) {
                    $sum += $v['credit'] - $v['debit'];
                }
            }

            return round($sum, 2);
        };
        $current = $yearStart ? $result($yearStart, $at) : $result(null, $at);
        $previous = $yearStart ? $result(null, Carbon::parse($yearStart)->subDay()->toDateString()) : 0.0;
        $unclassified = $accounts->whereNull('account_class')->count();

        return view('accounting.balance_sheet', ['assets' => $side('asset', 1), 'liabilities' => $side('liability', -1), 'equity' => $side('equity', -1),
            'current' => $current, 'previous' => $previous, 'at' => $at, 'unclassified' => $unclassified]);
    }

    /** Money in and out of a cash or bank account, day by day, with the balance. */
    public function cashBook(Request $request)
    {
        [$from, $to] = $this->period($request);
        $accounts = Account::where('is_postable', true)->where('code', 'like', '5%')->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => trim($a->code.' '.$a->name)])->all();
        $account = Account::find($request->integer('account_id') ?: array_key_first($accounts));
        $days = collect();
        $opening = 0.0;
        if ($account) {
            $before = Ledger::totals(null, Carbon::parse($from)->subDay()->toDateString(), $account->id)[$account->id] ?? ['debit' => 0, 'credit' => 0];
            $opening = round($before['debit'] - $before['credit'], 2);
            $days = \Illuminate\Support\Facades\DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_lines.account_id', $account->id)->whereBetween('journal_entries.entry_date', [$from, $to])
                ->selectRaw('journal_entries.entry_date as day, SUM(journal_lines.debit) as money_in, SUM(journal_lines.credit) as money_out')
                ->groupBy('journal_entries.entry_date')->orderBy('journal_entries.entry_date')->get();
        }

        return view('accounting.cash_book', compact('accounts', 'account', 'days', 'opening', 'from', 'to'));
    }

    /** Turnover of consultations and exams by speciality and by doctor (from the activity, not the ledger). */
    public function turnover(Request $request)
    {
        [$from, $to] = $this->period($request);
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $doctors = \App\Models\Doctor::with('doctorUser', 'department')->get()->keyBy('id');
        $rows = [];
        $add = function ($doctorId, string $kind, float $amount) use (&$rows, $doctors) {
            $doctor = $doctors[$doctorId] ?? null;
            $key = (int) $doctorId;
            $rows[$key] ??= ['doctor' => $doctor->doctorUser->full_name ?? __('messages.accounting.no_doctor'), 'speciality' => $doctor->department->title ?? '—',
                'consultations' => 0, 'consultation_amount' => 0.0, 'exams' => 0, 'exam_amount' => 0.0];
            $rows[$key][$kind === 'visit' ? 'consultations' : 'exams']++;
            $rows[$key][$kind === 'visit' ? 'consultation_amount' : 'exam_amount'] += $amount;
        };
        foreach (\App\Models\OpdPatientDepartment::whereBetween('appointment_date', [$start, $end])->get(['doctor_id', 'standard_charge']) as $v) {
            $add($v->doctor_id, 'visit', (float) $v->standard_charge);
        }
        foreach (\App\Models\ExamOrderItem::with('order:id,doctor_id,ordered_at')->whereHas('order', fn ($q) => $q->whereBetween('ordered_at', [$start, $end]))->get(['id', 'exam_order_id', 'total']) as $e) {
            $add($e->order->doctor_id, 'exam', (float) $e->total);
        }
        $rows = collect($rows)->sortBy([['speciality', 'asc'], ['doctor', 'asc']])->values();
        $specialities = $rows->groupBy('speciality')->map(fn ($g, $name) => ['speciality' => $name, 'amount' => $g->sum('consultation_amount') + $g->sum('exam_amount'),
            'consultations' => $g->sum('consultations'), 'exams' => $g->sum('exams')])->sortByDesc('amount')->values();

        return view('accounting.turnover', compact('rows', 'specialities', 'from', 'to'));
    }
}
