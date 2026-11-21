<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The price of one act in one price list, and the doctor and assistant shares
 * (kept on the default list's row; other lists fall back to it).
 */
class ItemPrice extends Model
{
    protected $table = 'item_prices';

    protected $fillable = ['priceable_type', 'priceable_id', 'price_list_id', 'price', 'doctor_share_percent', 'assistant_share_percent'];

    protected $casts = [
        'priceable_id' => 'integer',
        'price_list_id' => 'integer',
        'price' => 'float',
        'doctor_share_percent' => 'float',
        'assistant_share_percent' => 'float',
    ];

    /** Billable items that can be priced per list: tariff type => model, name column, base price column. */
    const ITEM_TYPES = [
        'charge' => [Charge::class, 'code', 'standard_charge'],
        'doctor_opd_charge' => [DoctorOPDCharge::class, null, 'standard_charge'],
        'pathology_test' => [PathologyTest::class, 'test_name', 'standard_charge'],
        'radiology_test' => [RadiologyTest::class, 'test_name', 'standard_charge'],
        'package' => [Package::class, 'name', 'total_amount'],
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public static function typeOf(Model $item): string
    {
        foreach (self::ITEM_TYPES as $type => [$class]) {
            if ($item instanceof $class) {
                return $type;
            }
        }
        throw new \InvalidArgumentException(get_class($item).' has no price list.');
    }

    public static function basePrice(Model $item): float
    {
        return (float) $item->{self::ITEM_TYPES[self::typeOf($item)][2]};
    }

    public static function for(Model $item, int $priceListId): ?self
    {
        return self::where(['priceable_type' => self::typeOf($item), 'priceable_id' => $item->getKey(), 'price_list_id' => $priceListId])->first();
    }

    /** Doctor share % of an act in a list: the list's own value, else the default list's, else 0. */
    public static function doctorShare(Model $item, PriceList $list): float
    {
        $own = self::for($item, $list->id)?->doctor_share_percent;
        if ($own !== null) {
            return $own;
        }
        $default = PriceList::defaultList();

        return (float) ($default ? self::for($item, $default->id)?->doctor_share_percent : 0);
    }
}
