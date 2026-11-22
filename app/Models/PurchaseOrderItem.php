<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'stockable_type', 'stockable_id', 'quantity', 'unit_cost', 'received_quantity'];

    protected $casts = ['quantity' => 'integer', 'received_quantity' => 'integer', 'unit_cost' => 'float'];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function remaining(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
