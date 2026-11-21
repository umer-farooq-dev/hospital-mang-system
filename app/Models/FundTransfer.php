<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money moved out of a register: to another register's open session, or to a safe or bank.
 */
class FundTransfer extends Model
{
    protected $table = 'fund_transfers';

    protected $fillable = ['from_session_id', 'from_counter_id', 'to_session_id', 'to_counter_id', 'destination', 'payment_method', 'amount', 'user_id', 'notes'];

    protected $casts = [
        'to_counter_id' => 'integer',
        'destination' => 'integer',
        'payment_method' => 'integer',
        'amount' => 'float',
    ];

    const DESTINATION_LIST = 'transfer_destination';

    public function fromCounter(): BelongsTo
    {
        return $this->belongsTo(CashCounter::class, 'from_counter_id');
    }

    public function toCounter(): BelongsTo
    {
        return $this->belongsTo(CashCounter::class, 'to_counter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDestinationNameAttribute(): string
    {
        return $this->toCounter ? $this->toCounter->code.' · '.$this->toCounter->name
            : (string) MasterListItem::label(self::DESTINATION_LIST, $this->destination);
    }
}
