<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseMedicine extends Model
{
    protected $fillable =
        [
            'purchase_no',
            'total',
            'discount',
            'tax',
            'net_amount',
            'payment_type',
            'payment_status',
            'payment_note',
            'note',
            'tenant_id',
            'store_id',
            'supplier_id',
            'purchase_order_id',
        ];

    const PURCHASE_MEDICINE_CASH = 0;

    const PURCHASE_MEDICINE_CHEQUE = 1;

    const PURCHASE_MEDICINE_STRIPE = 5;

    const PURCHASE_MEDICINE_RAZORPAY = 2;

    const PURCHASE_MEDICINE_PAYSTACK = 3;

    const PURCHASE_MEDICINE_PHONEPE = 4;

    const PURCHASE_MEDICINE_FLUTTERWAVE = 6;

    public function purchasedMedcines(): HasMany
    {
        return $this->hasMany(PurchasedMedicine::class, 'purchase_medicines_id');
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Store the goods go into: the one chosen, else the default store (saved on the purchase). */
    public function receivingStoreId(): int
    {
        if (! $this->store_id) {
            $storeId = \App\Utils\Stock::defaultStoreId();
            if (! $storeId) {
                throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(__('messages.stock.no_store'));
            }
            $this->forceFill(['store_id' => $storeId])->saveQuietly();
        }

        return (int) $this->store_id;
    }
}
