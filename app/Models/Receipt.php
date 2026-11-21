<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A payment at the cashier ("Reçu"), with one running number for the whole hospital.
 */
class Receipt extends Model
{
    protected $table = 'receipts';

    protected $fillable = ['receipt_number', 'cash_session_id', 'cash_counter_id', 'user_id', 'patient_id', 'payment_method', 'amount',
        'amount_received', 'change_given', 'cheque_number', 'cheque_drawer', 'voided_at'];

    protected $casts = [
        'receipt_number' => 'integer',
        'payment_method' => 'integer',
        'amount' => 'float',
        'amount_received' => 'float',
        'change_given' => 'float',
        'voided_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(CashCounter::class, 'cash_counter_id');
    }

    public function getPaymentMethodNameAttribute(): ?string
    {
        return PaymentMethod::label($this->payment_method);
    }

    /** Next running number: one more than the last, never below the start number in settings. */
    public static function nextNumber(): int
    {
        $start = (int) Setting::where('key', 'receipt_start_number')->value('value');

        return max((int) self::lockForUpdate()->max('receipt_number') + 1, $start, 1);
    }
}
