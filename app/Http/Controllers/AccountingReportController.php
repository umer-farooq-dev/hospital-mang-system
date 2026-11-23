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
}
