<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A tariff column of the old program (cash, insured, affiliate...). An act priced in the list
 * uses that price; otherwise the list's % is applied to the act's base price.
 *
 * @property int $id
 * @property string $name
 * @property string|null $lang_key
 * @property float $adjustment_percent
 * @property bool $is_default
 * @property bool $is_active
 */
class PriceList extends Model
{
    protected $table = 'price_lists';

    protected $fillable = ['name', 'lang_key', 'adjustment_percent', 'k_value', 'is_default', 'is_active', 'sort_order'];

    protected $casts = [
        'adjustment_percent' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'adjustment_percent' => 'required|numeric|min:-100|max:1000',
            'k_value' => 'nullable|numeric|min:0',
        ];
    }

    public function itemPrices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function getLabelAttribute(): string
    {
        if ($this->lang_key && __($this->lang_key) !== $this->lang_key) {
            return __($this->lang_key);
        }

        return $this->name;
    }

    public static function defaultList(): ?self
    {
        return self::where('is_default', true)->first() ?? self::orderBy('sort_order')->first();
    }

    /** Active lists for a dropdown, keeping a current (possibly deactivated) one. */
    public static function options($currentId = null): array
    {
        return self::where('is_active', true)->when($currentId, fn ($q) => $q->orWhere('id', $currentId))
            ->orderBy('sort_order')->get()->pluck('label', 'id')->all();
    }

    public function isInUse(): bool
    {
        return $this->is_default || $this->itemPrices()->whereNotNull('price')->exists()
            || Insurance::where('price_list_id', $this->id)->exists();
    }

    /**
     * Price of an act in this list at a given moment: the list's own price, or the base
     * price with the list's %, then the surcharge of the period it falls in (weekend, night).
     */
    public function priceFor(Model $item, $at = null): float
    {
        $own = ItemPrice::for($item, $this->id)?->price;
        $price = $own ?? ItemPrice::basePrice($item) * (1 + $this->adjustment_percent / 100);

        return round($price * (1 + PricePeriod::surchargeAt($at ?? now()) / 100), 2);
    }
}
