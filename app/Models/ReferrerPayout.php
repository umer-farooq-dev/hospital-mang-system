<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A commission paid to a referrer for a period. */
class ReferrerPayout extends Model
{
    protected $fillable = ['referrer_code', 'period_from', 'period_to', 'amount', 'paid_on', 'payment_method', 'reference', 'created_by'];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'paid_on' => 'date', 'amount' => 'float'];
}
