<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money given back for a receipt ("Ordre de remboursement"), paid out of the register of the
 * person refunding. The original receipt and its closed session stay unchanged.
 */
class Refund extends Model
{
    protected $table = 'refunds';

    protected $fillable = ['receipt_id', 'cash_session_id', 'cash_counter_id', 'user_id', 'reason', 'payment_method', 'amount', 'notes'];

    protected $casts = [
        'reason' => 'integer',
        'payment_method' => 'integer',
        'amount' => 'float',
    ];

    const REASON_LIST = 'refund_reason';

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReasonNameAttribute(): ?string
    {
        return MasterListItem::label(self::REASON_LIST, $this->reason);
    }
}
