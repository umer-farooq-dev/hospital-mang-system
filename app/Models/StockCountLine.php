<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockCountLine extends Model
{
    protected $fillable = ['stock_count_id', 'stock_batch_id', 'stockable_type', 'stockable_id', 'batch_no', 'expiry_date', 'unit_cost',
        'system_quantity', 'counted_quantity'];

    protected $casts = ['system_quantity' => 'integer', 'counted_quantity' => 'integer', 'unit_cost' => 'float'];

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Counted minus expected; null while not counted. */
    public function variance(): ?int
    {
        return $this->counted_quantity === null ? null : $this->counted_quantity - $this->system_quantity;
    }
}
