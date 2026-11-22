<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One value of a lab result, with the parameter's name, unit and range as they were when entered. */
class ExamResult extends Model
{
    protected $fillable = ['exam_order_item_id', 'pathology_parameter_id', 'label', 'value', 'unit', 'reference_range', 'is_abnormal', 'sort_order'];

    protected $casts = ['is_abnormal' => 'boolean'];

    /**
     * True when a numeric value is outside a range written as "4.5 - 11", "4,5-11", "< 5", "<= 5", "> 40" or "≥ 40";
     * null when the value or the range cannot be read.
     */
    public static function isOutside(?string $value, ?string $range): ?bool
    {
        $number = fn ($s) => is_numeric($s = str_replace([',', ' '], ['.', ''], (string) $s)) ? (float) $s : null;
        $v = $number($value);
        $range = trim(str_replace(['≤', '≥', '–', '—'], ['<=', '>=', '-', '-'], (string) $range));
        if ($v === null || $range === '') {
            return null;
        }
        if (preg_match('/^([<>]=?)\s*([\d.,]+)/', $range, $m)) {
            $limit = $number($m[2]);

            return match ($m[1]) {
                '<' => $v >= $limit, '<=' => $v > $limit, '>' => $v <= $limit, default => $v < $limit,
            };
        }
        if (preg_match('/^([\d.,]+)\s*-\s*([\d.,]+)/', $range, $m)) {
            return $v < $number($m[1]) || $v > $number($m[2]);
        }

        return null;
    }
}
