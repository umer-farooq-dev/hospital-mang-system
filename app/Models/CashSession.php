<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A cashier's session on a register, from opening (float) to closing ("Arrêt de caisse").
 */
class CashSession extends Model
{
    protected $table = 'cash_sessions';

    protected $fillable = ['cash_counter_id', 'user_id', 'opened_at', 'opening_float', 'closed_at', 'closing_number', 'expected_cash', 'counted_cash', 'notes'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_float' => 'float',
        'expected_cash' => 'float',
        'counted_cash' => 'float',
        'closing_number' => 'integer',
    ];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(CashCounter::class, 'cash_counter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /** Amount collected per payment method (valid receipts only). */
    public function totalsByMethod(): array
    {
        return $this->receipts()->whereNull('voided_at')->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')->pluck('total', 'payment_method')->map(fn ($t) => (float) $t)->all();
    }
}
