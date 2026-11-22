<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * How to read an analyser's export: the row holding the column titles, the column with the
 * sample number, and which column feeds which pathology parameter (mappings: title => parameter id).
 */
class LabImportProfile extends Model
{
    protected $fillable = ['name', 'header_row', 'sample_column', 'mappings', 'is_active'];

    protected $casts = ['mappings' => 'array', 'is_active' => 'boolean', 'header_row' => 'integer'];

    public static function key(?string $title): string
    {
        return mb_strtolower(trim((string) $title));
    }

    /** Rows of a CSV (separator detected), XLSX or XLS file as [column title => value], titles taken from $headerRow. */
    public static function readRows(UploadedFile $file, int $headerRow = 1): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $grid = $extension === 'csv' || $extension === 'txt'
            ? self::csvGrid((string) file_get_contents($file->getRealPath()))
            : (\Maatwebsite\Excel\Facades\Excel::toArray(new class {}, $file->getRealPath(), null,
                $extension === 'xls' ? \Maatwebsite\Excel\Excel::XLS : \Maatwebsite\Excel\Excel::XLSX)[0] ?? []);

        return self::rowsFromGrid($grid, $headerRow);
    }

    public static function csvGrid(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $content), fn ($l) => trim($l) !== ''));
        $first = $lines[0] ?? '';
        $separator = collect(["\t", ';', ',', '|'])->sortByDesc(fn ($s) => substr_count($first, $s))->first();

        return array_map(fn ($line) => str_getcsv($line, $separator), $lines);
    }

    public static function rowsFromGrid(array $grid, int $headerRow): array
    {
        $grid = array_values($grid);
        $titles = array_map(fn ($t) => trim((string) $t), $grid[max(1, $headerRow) - 1] ?? []);
        $rows = [];
        foreach (array_slice($grid, max(1, $headerRow)) as $cells) {
            $row = [];
            foreach ($titles as $i => $title) {
                if ($title !== '') {
                    $row[$title] = isset($cells[$i]) ? trim((string) $cells[$i]) : null;
                }
            }
            if (array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** sample number => [parameter id => value], from the rows and this profile's columns (titles compared without case). */
    public function plan(array $rows): array
    {
        $mappings = collect($this->mappings ?? [])->mapWithKeys(fn ($parameterId, $title) => [self::key($title) => (int) $parameterId])->filter();
        $sampleKey = self::key($this->sample_column);
        $plan = [];
        foreach ($rows as $row) {
            $row = collect($row)->mapWithKeys(fn ($v, $k) => [self::key($k) => $v]);
            $sample = strtoupper(trim((string) $row->get($sampleKey)));
            if ($sample === '') {
                continue;
            }
            foreach ($mappings as $title => $parameterId) {
                $value = $row->get($title);
                if ($value !== null && $value !== '') {
                    $plan[$sample][$parameterId] = (string) $value;
                }
            }
        }

        return $plan;
    }
}
