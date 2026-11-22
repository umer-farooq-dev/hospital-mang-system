<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = ['number', 'certificate_template_id', 'title', 'patient_id', 'doctor_id', 'ipd_patient_department_id',
        'issued_at', 'start_date', 'end_date', 'body', 'created_by'];

    protected $casts = ['issued_at' => 'datetime', 'start_date' => 'date', 'end_date' => 'date'];

    /** Next number: PREFIX-YEAR-0001, counting again from 1 each year. */
    public static function nextNumber(string $prefix, int $year, ?string $last): string
    {
        $sequence = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $sequence = (int) $m[1] + 1;
        }

        return sprintf('%s-%d-%04d', $prefix !== '' ? $prefix : 'CM', $year, $sequence);
    }

    public static function newNumber(): string
    {
        $prefix = trim((string) Setting::where('key', 'certificate_prefix')->value('value')) ?: 'CM';
        $year = (int) now()->format('Y');
        $last = self::where('number', 'like', $prefix . '-' . $year . '-%')->orderByDesc('id')->value('number');

        return self::nextNumber($prefix, $year, $last);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
