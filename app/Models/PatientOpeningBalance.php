<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What a patient owed on the first day (imported); the cashier collects it like any other item. */
class PatientOpeningBalance extends Model
{
    protected $fillable = ['patient_id', 'amount', 'label', 'as_of', 'data_import_run_id'];

    protected $casts = ['amount' => 'float', 'as_of' => 'date'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
