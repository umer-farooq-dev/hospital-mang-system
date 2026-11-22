<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorStatementLine extends Model
{
    const KINDS = ['consultation', 'exam', 'operation', 'fixed', 'hours', 'other', 'withholding'];

    protected $fillable = ['doctor_statement_id', 'kind', 'source_type', 'source_id', 'line_date', 'description', 'base_amount', 'percent', 'amount'];

    protected $casts = ['base_amount' => 'float', 'percent' => 'float', 'amount' => 'float', 'line_date' => 'date'];
}
