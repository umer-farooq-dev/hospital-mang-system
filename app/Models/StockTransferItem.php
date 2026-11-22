<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'stockable_type', 'stockable_id', 'quantity_requested', 'quantity_approved', 'quantity_sent'];

    protected $casts = ['quantity_requested' => 'integer', 'quantity_approved' => 'integer', 'quantity_sent' => 'integer'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }
}
