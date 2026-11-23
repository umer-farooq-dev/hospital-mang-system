<?php

namespace App\Utils;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** The one place journal entries are written: balanced, in an open fiscal year, after the lock date. */
class Ledger
{
    public static function fail(string $key, array $replace = []): never
    {
        throw new UnprocessableEntityHttpException(__('messages.accounting.errors.'.$key, $replace));
    }

    public static function lockDate(): ?string
    {
        $value = Stock::setting('accounting_lock_date', '');

        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    /** Open fiscal year containing the date, or refuse. */
    public static function yearFor(Carbon $date): FiscalYear
    {
        $year = FiscalYear::whereDate('starts_on', '<=', $date->toDateString())->whereDate('ends_on', '>=', $date->toDateString())->first();
        if (! $year) {
            self::fail('no_year', ['date' => $date->format('d/m/Y')]);
        }
        if ($year->status !== 'open') {
            self::fail('year_closed', ['year' => $year->name]);
        }
        $lock = self::lockDate();
        if ($lock && $date->toDateString() <= $lock) {
            self::fail('locked', ['date' => Carbon::parse($lock)->format('d/m/Y')]);
        }

        return $year;
    }

    /**
     * Post an entry.
     *
     * @param  array  $lines  [['account_id' => id, 'debit' => x, 'credit' => y, 'label' => ?], ...]
     * @param  array  $source  ['type' => ?, 'id' => ?, 'event' => ?]
     */
    public static function post(Journal|string $journal, $date, string $label, array $lines, ?string $reference = null, array $source = [], ?int $userId = null): JournalEntry
    {
        $journal = $journal instanceof Journal ? $journal : Journal::where('code', $journal)->first();
        if (! $journal || ! $journal->is_active) {
            self::fail('no_journal');
        }
        $date = Carbon::parse($date);
        $lines = array_values(array_filter(array_map(fn ($l) => ['account_id' => (int) ($l['account_id'] ?? 0), 'label' => $l['label'] ?? null,
            'debit' => round(max(0, (float) ($l['debit'] ?? 0)), 2), 'credit' => round(max(0, (float) ($l['credit'] ?? 0)), 2)], $lines),
            fn ($l) => $l['account_id'] && ($l['debit'] > 0 || $l['credit'] > 0)));
        if (count($lines) < 2) {
            self::fail('two_lines');
        }
        foreach ($lines as $l) {
            if ($l['debit'] > 0 && $l['credit'] > 0) {
                self::fail('both_sides');
            }
        }
        $debit = round(array_sum(array_column($lines, 'debit')), 2);
        $credit = round(array_sum(array_column($lines, 'credit')), 2);
        if (abs($debit - $credit) > 0.004) {
            self::fail('unbalanced', ['debit' => $debit, 'credit' => $credit]);
        }
        $accounts = Account::whereIn('id', array_column($lines, 'account_id'))->get()->keyBy('id');
        foreach ($lines as $l) {
            $account = $accounts[$l['account_id']] ?? null;
            if (! $account || ! $account->is_postable || ! $account->status) {
                self::fail('bad_account', ['account' => $account ? trim($account->code.' '.$account->name) : '#'.$l['account_id']]);
            }
        }

        return DB::transaction(function () use ($journal, $date, $label, $lines, $reference, $source, $userId) {
            $year = self::yearFor($date);
            DB::table('journals')->where('id', $journal->id)->lockForUpdate()->first(); // numbers in order per journal
            $prefix = $journal->code.$date->format('y').'-';
            $last = JournalEntry::where('journal_id', $journal->id)->where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
            $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
            $entry = JournalEntry::create(['journal_id' => $journal->id, 'fiscal_year_id' => $year->id, 'number' => $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                'entry_date' => $date->toDateString(), 'reference' => $reference, 'label' => mb_substr($label, 0, 255),
                'source_type' => $source['type'] ?? null, 'source_id' => $source['id'] ?? null, 'source_event' => $source['event'] ?? null,
                'reversal_of' => $source['reversal_of'] ?? null, 'created_by' => $userId ?? auth()->id()]);
            foreach ($lines as $l) {
                $entry->lines()->create($l);
            }

            return $entry;
        });
    }

    /** Undo an entry with the opposite entry (on the given date, by default today). */
    public static function reverse(JournalEntry $entry, $date = null, ?int $userId = null): JournalEntry
    {
        if ($entry->reversal_of || $entry->reversedBy()) {
            self::fail('already_reversed');
        }
        $lines = $entry->lines->map(fn ($l) => ['account_id' => $l->account_id, 'label' => $l->label, 'debit' => $l->credit, 'credit' => $l->debit])->all();

        return self::post($entry->journal, $date ?? now(), __('messages.accounting.reversal_of', ['number' => $entry->number]).' · '.$entry->label, $lines,
            $entry->reference, ['type' => $entry->source_type, 'id' => $entry->source_id, 'event' => $entry->source_event, 'reversal_of' => $entry->id], $userId);
    }

    /**
     * Balance per account over a period: [account_id => ['debit' => , 'credit' => ]].
     */
    public static function totals(?string $from, string $to, ?int $accountId = null): array
    {
        return DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->when($from, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from))->where('journal_entries.entry_date', '<=', $to)
            ->when($accountId, fn ($q) => $q->where('journal_lines.account_id', $accountId))
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) as debit, SUM(journal_lines.credit) as credit')
            ->groupBy('journal_lines.account_id')->get()
            ->mapWithKeys(fn ($r) => [$r->account_id => ['debit' => round((float) $r->debit, 2), 'credit' => round((float) $r->credit, 2)]])->all();
    }
}
