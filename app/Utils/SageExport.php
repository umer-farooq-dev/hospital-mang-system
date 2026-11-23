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
        'date_format' => 'd/m/Y', 'decimal' => ',', 'header' => true, 'encoding' => 'UTF-8', 'extension' => 'csv'];

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

    /** One row per entry line. */
    public static function rows(Collection $entries, array $options): array
    {
        $number = fn (float $v) => $v ? number_format($v, 2, $options['decimal'], '') : '';
        $rows = [];
        foreach ($entries as $entry) {
            foreach ($entry->lines as $line) {
                $values = [
                    'journal' => $entry->journal->code ?? '', 'date' => $entry->entry_date->format($options['date_format']), 'number' => $entry->number,
                    'reference' => (string) $entry->reference, 'account' => (string) ($line->account->code ?? ''), 'label' => mb_substr($line->label ?: $entry->label, 0, 255),
                    'debit' => $number($line->debit), 'credit' => $number($line->credit), 'amount' => $number($line->debit ?: $line->credit),
                    'direction' => $line->debit > 0 ? 'D' : 'C', 'year' => $entry->entry_date->format('Y'),
                ];
                $rows[] = array_map(fn ($c) => $values[$c], $options['columns']);
            }
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
