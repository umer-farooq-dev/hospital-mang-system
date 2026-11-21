<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A cash register ("caisse"). Each payment method posts to its own accounting account.
 */
class CashCounter extends Model
{
    protected $table = 'cash_counters';

    protected $fillable = ['code', 'name', 'is_reception', 'account_cash', 'account_cheque', 'account_electronic', 'is_active'];

    protected $casts = [
        'is_reception' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cash_counter_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function openSession(): ?CashSession
    {
        return $this->sessions()->whereNull('closed_at')->latest('id')->first();
    }
}
