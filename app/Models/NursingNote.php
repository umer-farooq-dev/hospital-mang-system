<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingNote extends Model
{
    protected $fillable = ['ipd_patient_department_id', 'shift', 'noted_at', 'note', 'recorded_by'];

    protected $casts = ['noted_at' => 'datetime'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
