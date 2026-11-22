<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalReading extends Model
{
    protected $fillable = ['ipd_patient_department_id', 'vital_type_id', 'value', 'measured_at', 'note', 'recorded_by'];

    protected $casts = ['value' => 'float', 'measured_at' => 'datetime'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VitalType::class, 'vital_type_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
