<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** ICD-10 (CIM-10) code, imported by the admin from the WHO / national list. */
class Icd10Code extends Model
{
    protected $table = 'icd10_codes';

    protected $fillable = ['code', 'title', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function normalize(string $code): string
    {
        return strtoupper(trim(str_replace(' ', '', $code)));
    }

    public function getLabelAttribute(): string
    {
        return $this->code . ' — ' . $this->title;
    }

    public static function labelFor(?string $code): ?string
    {
        if (! $code) {
            return null;
        }
        $title = self::where('code', $code)->value('title');

        return $title ? $code . ' — ' . $title : $code;
    }

    /**
     * Rows of a CSV/TXT export: "code;title", "code,title", "code|title" or tab separated,
     * with or without a header line; quotes are removed. Lines without a code-like first column are skipped.
     */
    public static function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $separator = collect(["\t", ';', '|', ','])->first(fn ($s) => str_contains($line, $s));
            if (! $separator) {
                continue;
            }
            $cells = array_map(fn ($c) => trim($c, " \"'"), str_getcsv($line, $separator));
            $code = self::normalize($cells[0] ?? '');
            $title = trim(implode($separator === ',' ? ', ' : ' ', array_slice($cells, 1)));
            if (! preg_match('/^[A-Z]\d{2}(\.?[0-9A-Z]{1,4})?[+*]?$/', $code) || $title === '') {
                continue;
            }
            if (strlen($code) > 3 && $code[3] !== '.') {
                $code = substr($code, 0, 3) . '.' . substr($code, 3);
            }
            $rows[$code] = mb_substr($title, 0, 255);
        }

        return $rows;
    }
}
