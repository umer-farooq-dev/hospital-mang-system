<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Certificate template. Seeded templates show their translated name and text (lang_key)
 * until the admin edits them; then the admin's own text is used in every language.
 */
class CertificateTemplate extends Model
{
    protected $fillable = ['name', 'lang_key', 'body', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    const PLACEHOLDERS = ['hospital', 'patient_name', 'patient_id', 'age', 'gender', 'dob', 'date', 'doctor',
        'start_date', 'end_date', 'days', 'ipd_number', 'admission_date', 'discharge_date', 'notes'];

    public function getLabelAttribute(): string
    {
        $key = $this->lang_key ? $this->lang_key . '.name' : null;

        return $key && __($key) !== $key ? __($key) : $this->name;
    }

    public function getTextAttribute(): string
    {
        $key = $this->lang_key ? $this->lang_key . '.body' : null;
        if ($this->body === null && $key && __($key) !== $key) {
            return __($key);
        }

        return (string) $this->body;
    }

    /** Replaces {placeholders}; unknown ones are left as typed so the doctor sees them. */
    public static function fillPlaceholders(string $text, array $values): string
    {
        $pairs = [];
        foreach (self::PLACEHOLDERS as $name) {
            $pairs['{' . $name . '}'] = (string) ($values[$name] ?? '');
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", strtr($text, $pairs)));
    }

    public static function active()
    {
        return self::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }
}
