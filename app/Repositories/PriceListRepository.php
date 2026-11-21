<?php

namespace App\Repositories;

use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\PricePeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Price lists, surcharge periods and the tariff grid (one price per act and list).
 */
class PriceListRepository
{
    const PER_PAGE = 50;

    /**
     * Save the edited lists, add the new one and remove the ticked ones not in use.
     * Exactly one list is the default (cash) list.
     */
    public function saveLists(array $rows, ?array $new, $defaultId): void
    {
        DB::transaction(function () use ($rows, $new, $defaultId) {
            foreach ($rows as $id => $row) {
                $list = PriceList::findOrFail($id);
                if (! empty($row['delete'])) {
                    if ($list->isInUse()) {
                        throw ValidationException::withMessages(['lists' => __('messages.price_list.in_use', ['name' => $list->label])]);
                    }
                    $list->delete();

                    continue;
                }
                $data = $this->renamed($list, $this->validated($row, PriceList::rules()));
                $list->update($data + ['is_active' => ! empty($row['is_active']) || $list->is_default]);
            }
            if (! empty($new['name'])) {
                $data = $this->validated($new, PriceList::rules());
                PriceList::create($data + ['is_active' => true, 'sort_order' => (int) PriceList::max('sort_order') + 1]);
            }
            if ($defaultId && PriceList::whereKey($defaultId)->exists()) {
                PriceList::where('id', '!=', $defaultId)->update(['is_default' => false]);
                PriceList::whereKey($defaultId)->update(['is_default' => true, 'is_active' => true]);
            }
        });
    }

    public function savePeriods(array $rows, ?array $new): void
    {
        DB::transaction(function () use ($rows, $new) {
            foreach ($rows as $id => $row) {
                $period = PricePeriod::findOrFail($id);
                if (! empty($row['delete'])) {
                    $period->delete();

                    continue;
                }
                $period->update($this->renamed($period, $this->periodData($row)) + ['is_active' => ! empty($row['is_active'])]);
            }
            if (! empty($new['name'])) {
                PricePeriod::create($this->periodData($new) + ['is_active' => true]);
            }
        });
    }

    /**
     * Seeded rows show a translated name. Saving that same text keeps the translation;
     * a different text is the admin's own name and replaces it in every language.
     */
    private function renamed($row, array $data): array
    {
        if ($data['name'] === $row->label) {
            unset($data['name']);
        } else {
            $data['lang_key'] = null;
        }

        return $data;
    }

    private function periodData(array $row): array
    {
        $data = $this->validated($row, PricePeriod::rules());
        $days = array_unique(array_map('intval', $data['days'] ?? []));
        sort($days);

        return [
            'name' => $data['name'],
            'days' => $days ? implode(',', $days) : null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'surcharge_percent' => $data['surcharge_percent'],
        ];
    }

    private function validated(array $row, array $rules): array
    {
        $validator = Validator::make($row, $rules, [], [
            'name' => __('messages.price_list.name'),
            'adjustment_percent' => __('messages.price_list.adjustment_percent'),
            'surcharge_percent' => __('messages.price_list.surcharge_percent'),
        ]);

        return $validator->validate();
    }

    /** Items of one type with their prices per list, for the tariff grid. */
    public function tariffItems(string $type, ?string $search): LengthAwarePaginator
    {
        [$class, $nameColumn] = ItemPrice::ITEM_TYPES[$type];
        $query = $class::query();
        if ($type === 'doctor_opd_charge') {
            $query->with('doctor.doctorUser')->when($search, fn ($q) => $q->whereHas('doctor.doctorUser',
                fn ($u) => $u->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%$search%")));
        } else {
            $query->when($search, fn ($q) => $q->where($nameColumn, 'like', "%$search%"))->orderBy($nameColumn);
        }
        if ($type === 'charge') {
            $query->with('chargeCategory');
        }
        $items = $query->paginate(self::PER_PAGE)->withQueryString();

        $prices = ItemPrice::where('priceable_type', $type)->whereIn('priceable_id', $items->pluck('id'))->get()
            ->groupBy('priceable_id')->map->keyBy('price_list_id');
        $items->getCollection()->each(fn ($item) => $item->setRelation('listPrices', $prices->get($item->id, collect())));

        return $items;
    }

    public static function itemName($item, string $type): string
    {
        return match ($type) {
            'doctor_opd_charge' => $item->doctor->doctorUser->full_name ?? '#'.$item->id,
            'charge' => trim($item->code.' — '.($item->chargeCategory->name ?? '')),
            default => (string) $item->{ItemPrice::ITEM_TYPES[$type][1]},
        };
    }

    /**
     * rows[item id][list id] = price (empty = use the list's %),
     * rows[item id][doctor] and [assistant] = shares on the default list.
     */
    public function saveTariff(string $type, array $rows): void
    {
        [$class] = ItemPrice::ITEM_TYPES[$type];
        $default = PriceList::defaultList();
        $listIds = PriceList::pluck('id')->all();
        $rules = ['*.*' => 'nullable|numeric|min:0', '*.doctor' => 'nullable|numeric|min:0|max:100', '*.assistant' => 'nullable|numeric|min:0|max:100'];
        Validator::make($rows, $rules, [], ['*.*' => __('messages.price_list.price')])->validate();

        DB::transaction(function () use ($class, $type, $rows, $default, $listIds) {
            $existing = $class::whereIn('id', array_keys($rows))->pluck('id')->all();
            foreach ($rows as $itemId => $row) {
                if (! in_array((int) $itemId, $existing)) {
                    continue;
                }
                foreach ($listIds as $listId) {
                    if ($default && $listId === $default->id) {
                        // The default list's price is the item's own base price; its row keeps the shares.
                        $values = ['price' => null, 'doctor_share_percent' => $this->number($row['doctor'] ?? null),
                            'assistant_share_percent' => $this->number($row['assistant'] ?? null)];
                    } elseif (array_key_exists($listId, $row)) {
                        $values = ['price' => $this->number($row[$listId])];
                    } else {
                        continue; // list not on the screen (inactive): keep its prices
                    }
                    $key = ['priceable_type' => $type, 'priceable_id' => (int) $itemId, 'price_list_id' => $listId];
                    if (array_filter($values, fn ($v) => $v !== null) === []) {
                        ItemPrice::where($key)->delete();
                    } else {
                        ItemPrice::updateOrCreate($key, $values);
                    }
                }
            }
        });
    }

    private function number($value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
