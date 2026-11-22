<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Reorder level and quantity of one item in one store. */
class StockLevel extends Model
{
    protected $fillable = ['store_id', 'stockable_type', 'stockable_id', 'reorder_level', 'reorder_quantity'];

    protected $casts = ['reorder_level' => 'integer', 'reorder_quantity' => 'integer'];

    /** What to order: the reorder quantity, or at least enough to reach the level again. */
    public static function suggested(int $level, int $quantity, int $onHand, int $onOrder): int
    {
        $gap = $level - $onHand - $onOrder;

        return $gap <= 0 ? 0 : max($quantity, $gap);
    }
}
