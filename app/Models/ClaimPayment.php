<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Money received from a payer against one claim invoice. */
class ClaimPayment extends Model
{
    protected $fillable = ['claim_invoice_id', 'paid_on', 'amount', 'payment_method', 'reference', 'note', 'created_by'];

    protected $casts = ['paid_on' => 'date', 'amount' => 'float'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ClaimInvoice::class, 'claim_invoice_id');
    }
}
