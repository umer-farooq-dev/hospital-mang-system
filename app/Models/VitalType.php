<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Vital sign measured by nurses, with its unit and normal range (Settings > Inpatient). */
class VitalType extends Model
{
    protected $fillable = ['name', 'lang_key', 'unit', 'normal_min', 'normal_max', 'sort_order', 'is_active'];

    protected $casts = ['normal_min' => 'float', 'normal_max' => 'float', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    /** Translated name for seeded types, the admin's own name otherwise. */
    public function getLabelAttribute(): string
    {
        return $this->lang_key && __($this->lang_key) !== $this->lang_key ? __($this->lang_key) : $this->name;
    }

    public function getRangeAttribute(): string
    {
        if ($this->normal_min === null && $this->normal_max === null) {
            return '';
        }

        $min = $this->normal_min !== null ? $this->normal_min + 0 : '…';
        $max = $this->normal_max !== null ? $this->normal_max + 0 : '…';

        return trim($min . ' – ' . $max . ' ' . $this->unit);
    }

    public function isAbnormal(?float $value): bool
    {
        return $value !== null && (($this->normal_min !== null && $value < $this->normal_min) || ($this->normal_max !== null && $value > $this->normal_max));
    }

    public static function active()
    {
        return self::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }
}
