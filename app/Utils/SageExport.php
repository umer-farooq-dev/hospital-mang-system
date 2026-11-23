<?php

namespace App\Utils;

use App\Models\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Journal entries as a file the accounting software imports (Sage journal import), laid out as the admin sets:
 * columns and their order, separator, date and decimal format, header row, encoding.
 */
class SageExport
{
    const COLUMNS = ['journal', 'date', 'number', 'reference', 'account', 'label', 'debit', 'credit', 'amount', 'direction', 'year'];

    const DEFAULTS = ['columns' => ['journal', 'date', 'number', 'account', 'reference', 'label', 'debit', 'credit'], 'separator' => ';',
        'date_format' => 'd/m/Y', 'decimal' => ',', 'header' => true, 'encoding' => 'UTF-8', 'extension' => 'csv', 'group' => 'none'];

    // none: every line; entry: lines of an entry on the same account and side are added up;
    // day: all entries of a journal on one day become one entry, one line per account and side.
    const GROUPS = ['none', 'entry', 'day'];

    const SEPARATORS = [';' => ';', ',' => ',', 'tab' => 'Tab', '|' => '|'];

    const DATE_FORMATS = ['d/m/Y' => '31/12/2026', 'dmy' => '311226', 'dmY' => '31122026', 'Y-m-d' => '2026-12-31', 'd.m.Y' => '31.12.2026'];

    const ENCODINGS = ['UTF-8' => 'UTF-8', 'Windows-1252' => 'Windows-1252 (ANSI)'];

    public static function options(): array
    {
        $saved = json_decode((string) Stock::setting('sage_export', '{}'), true) ?: [];
        $options = array_merge(self::DEFAULTS, $saved);
        $options['columns'] = array_values(array_intersect((array) $options['columns'], self::COLUMNS)) ?: self::DEFAULTS['columns'];

        return $options;
    }

    public static function entries(Carbon $from, Carbon $to, bool $onlyNew, ?int $journalId = null): Collection
    {
        return JournalEntry::with('journal', 'lines.account')->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->when($onlyNew, fn ($q) => $q->whereNull('exported_at'))->when($journalId, fn ($q) => $q->where('journal_id', $journalId))
            ->orderBy('entry_date')->orderBy('id')->get();
    }

    /** One row per entry line, or per group of lines (option "group"). */
    public static function rows(Collection $entries, array $options): array
    {
        $number = fn (float $v) => $v ? number_format($v, 2, $options['decimal'], '') : '';
        $group = in_array($options['group'] ?? 'none', self::GROUPS, true) ? ($options['group'] ?? 'none') : 'none';
        $items = [];
        foreach ($entries as $entry) {
            $journal = $entry->journal->code ?? '';
            $day = $entry->entry_date;
            foreach ($entry->lines as $line) {
                $side = $line->debit > 0 ? 'D' : 'C';
                $account = (string) ($line->account->code ?? '');
                $key = match ($group) {
                    'entry' => $entry->id.'|'.$account.'|'.$side,
                    'day' => $journal.'|'.$day->toDateString().'|'.$account.'|'.$side,
                    default => $line->id,
                };
                $items[$key] ??= ['journal' => $journal, 'day' => $day, 'number' => $group === 'day' ? $journal.$day->format('ymd') : $entry->number,
                    'reference' => $group === 'day' ? '' : (string) $entry->reference, 'account' => $account, 'side' => $side, 'debit' => 0.0, 'credit' => 0.0,
                    'label' => $group === 'day' ? __('messages.sage.day_label', ['journal' => $journal, 'date' => $day->format('d/m/Y')])
                        : ($group === 'entry' ? $entry->label : ($line->label ?: $entry->label))];
                $items[$key]['debit'] += (float) $line->debit;
                $items[$key]['credit'] += (float) $line->credit;
            }
        }
        $rows = [];
        foreach ($items as $item) {
            $values = [
                'journal' => $item['journal'], 'date' => $item['day']->format($options['date_format']), 'number' => $item['number'],
                'reference' => $item['reference'], 'account' => $item['account'], 'label' => mb_substr((string) $item['label'], 0, 255),
                'debit' => $number(round($item['debit'], 2)), 'credit' => $number(round($item['credit'], 2)), 'amount' => $number(round($item['debit'] ?: $item['credit'], 2)),
                'direction' => $item['side'], 'year' => $item['day']->format('Y'),
            ];
            $rows[] = array_map(fn ($c) => $values[$c], $options['columns']);
        }

        return $rows;
    }

    public static function content(array $rows, array $options): string
    {
        $separator = $options['separator'] === 'tab' ? "\t" : $options['separator'];
        $out = fopen('php://temp', 'w+');
        if ($options['header']) {
            fputcsv($out, array_map(fn ($c) => __('messages.sage.columns.'.$c), $options['columns']), $separator);
        }
        foreach ($rows as $row) {
            fputcsv($out, $row, $separator);
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return $options['encoding'] === 'Windows-1252' ? mb_convert_encoding($content, 'Windows-1252', 'UTF-8') : "\xEF\xBB\xBF".$content;
    }
}
