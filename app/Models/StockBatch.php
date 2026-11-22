<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Quantity of one item, one batch and expiry, in one store. Changed only by App\Utils\Stock. */
class StockBatch extends Model
{
    protected $fillable = ['store_id', 'stockable_type', 'stockable_id', 'batch_no', 'expiry_date', 'unit_cost', 'quantity', 'supplier_id'];

    protected $casts = ['quantity' => 'integer', 'unit_cost' => 'float', 'store_id' => 'integer'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date < now()->toDateString();
    }

    public function expiresWithin(int $days): bool
    {
        return $this->expiry_date !== null && $this->expiry_date <= now()->addDays($days)->toDateString();
    }
}
