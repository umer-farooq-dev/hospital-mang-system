<?php

namespace App\Utils;

use App\Models\Medicine;
use App\Models\Setting;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * The one place stock changes. Every change is a ledger row (stock_movements) on a batch of a store;
 * the item's available quantity is only a cached total of its batches.
 */
class Stock
{
    const OPENING = 'opening';

    const RECEIPT = 'receipt';

    const SALE = 'sale';

    const RETURN = 'return';      // goods back on the same document (bill edited or moved to another store)

    const REVERSAL = 'reversal';  // a document undone (purchase or bill deleted)

    const TRANSFER_OUT = 'transfer_out';

    const TRANSFER_IN = 'transfer_in';

    const SUPPLIER_RETURN = 'supplier_return';

    const ADJUSTMENT = 'adjustment';  // stock count difference

    const ISSUE = 'issue';            // general item given to a department

    const TYPES = [self::OPENING, self::RECEIPT, self::SALE, self::RETURN, self::REVERSAL, self::TRANSFER_OUT, self::TRANSFER_IN, self::SUPPLIER_RETURN, self::ADJUSTMENT, self::ISSUE];

    // Item class => table whose available_quantity caches the total.
    const TOTALS = [Medicine::class => 'medicines', \App\Models\Item::class => 'items'];

    public static function setting(string $key, $default = null)
    {
        $value = Setting::where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : $value;
    }

    public static function fefo(): bool
    {
        return self::setting('stock_fefo', '1') === '1';
    }

    public static function allowNegative(): bool
    {
        return self::setting('stock_allow_negative', '0') === '1';
    }

    public static function sellExpired(): bool
    {
        return self::setting('stock_sell_expired', '0') === '1';
    }

    public static function expiryAlertDays(): int
    {
        return max(0, (int) self::setting('stock_expiry_alert_days', 90));
    }

    /** Store used when a document names none: the setting, else the first active selling store. */
    public static function defaultStoreId(): ?int
    {
        $id = (int) self::setting('pharmacy_default_store', 0);
        if ($id && Store::where('id', $id)->where('is_active', true)->exists()) {
            return $id;
        }

        return Store::where('is_active', true)->orderByDesc('can_sell')->orderBy('sort_order')->orderBy('id')->value('id');
    }

    /** Store for general items (linen, consumables) when an entry names none. */
    public static function inventoryStoreId(): ?int
    {
        $id = (int) self::setting('inventory_default_store', 0);

        return $id && Store::where('id', $id)->where('is_active', true)->exists() ? $id : self::defaultStoreId();
    }

    public static function onHand(Model $item, int $storeId): int
    {
        return (int) self::batches($item->getMorphClass(), $item->getKey(), $storeId)->sum('quantity');
    }

    private static function batches(string $type, int $id, int $storeId)
    {
        return StockBatch::where('store_id', $storeId)->where('stockable_type', $type)->where('stockable_id', $id);
    }

    /** Put goods into a store. The same batch number and expiry add up; the cost becomes the weighted average. */
    public static function receive(Model $item, int $storeId, int $qty, array $batch, Model $document, string $type = self::RECEIPT, ?string $note = null): StockMovement
    {
        if ($qty <= 0) {
            throw new UnprocessableEntityHttpException(__('messages.stock.quantity_positive'));
        }
        $batchNo = trim((string) ($batch['batch_no'] ?? '')) ?: null;
        $expiry = ! empty($batch['expiry_date']) ? Carbon::parse($batch['expiry_date'])->toDateString() : null;
        $cost = round((float) ($batch['unit_cost'] ?? 0), 2);

        $row = self::batches($item->getMorphClass(), $item->getKey(), $storeId)->where('batch_no', $batchNo)->where('expiry_date', $expiry)->lockForUpdate()->first();
        if ($row) {
            $total = $row->quantity + $qty;
            $row->unit_cost = $row->quantity > 0 && $total > 0 ? round(($row->unit_cost * $row->quantity + $cost * $qty) / $total, 2) : $cost;
            $row->quantity = $total;
            $row->save();
        } else {
            $row = StockBatch::create(['store_id' => $storeId, 'stockable_type' => $item->getMorphClass(), 'stockable_id' => $item->getKey(),
                'batch_no' => $batchNo, 'expiry_date' => $expiry, 'unit_cost' => $cost, 'quantity' => $qty, 'supplier_id' => $batch['supplier_id'] ?? null]);
        }

        return self::log($row, $qty, $type, $document, $note, $cost);
    }

    /** Take goods out of a store: earliest expiry first (FEFO) or oldest batch first; expired batches are skipped unless allowed. */
    public static function issue(Model $item, int $storeId, int $qty, Model $document, string $type = self::SALE, ?string $note = null): Collection
    {
        $query = self::batches($item->getMorphClass(), $item->getKey(), $storeId)->where('quantity', '>', 0)->lockForUpdate();
        if (! self::sellExpired()) {
            $query->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString()));
        }
        if (self::fefo()) {
            $query->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date');
        }
        $batches = $query->orderBy('id')->get();
        $usable = (int) $batches->sum('quantity');
        if ($usable < $qty && ! self::allowNegative()) {
            throw new UnprocessableEntityHttpException(__('messages.stock.not_enough', ['available' => max(0, $usable),
                'item' => self::name($item->getMorphClass(), $item->getKey()), 'store' => Store::whereKey($storeId)->value('name')]));
        }

        $moves = collect();
        $left = $qty;
        foreach ($batches as $batch) {
            $take = min($left, $batch->quantity);
            if ($take <= 0) {
                break;
            }
            $batch->decrement('quantity', $take);
            $moves->push(self::log($batch, -$take, $type, $document, $note));
            $left -= $take;
        }
        if ($left > 0) { // negative stock allowed: the rest goes below zero on the last batch (or an unnamed one)
            $batch = $batches->last() ?? self::batches($item->getMorphClass(), $item->getKey(), $storeId)->orderByDesc('id')->first()
                ?? StockBatch::create(['store_id' => $storeId, 'stockable_type' => $item->getMorphClass(), 'stockable_id' => $item->getKey(), 'quantity' => 0]);
            $batch->decrement('quantity', $left);
            $moves->push(self::log($batch, -$left, $type, $document, $note));
        }

        return $moves;
    }

    /** Correct one batch by a counted difference (may go below zero: the count is the truth). */
    public static function adjust(StockBatch $batch, int $delta, Model $document, ?string $note = null): ?StockMovement
    {
        if ($delta === 0) {
            return null;
        }
        $batch = StockBatch::lockForUpdate()->find($batch->id);
        $batch->increment('quantity', $delta);

        return self::log($batch, $delta, self::ADJUSTMENT, $document, $note);
    }

    /** Net quantity per item and store that a document moved (negative = taken out). */
    public static function net(Model $document): Collection
    {
        return StockMovement::forDocument($document)
            ->selectRaw('stockable_type, stockable_id, store_id, SUM(quantity) as net')
            ->groupBy('stockable_type', 'stockable_id', 'store_id')->get()
            ->filter(fn ($row) => (int) $row->net !== 0)->values();
    }

    /**
     * Make what a document has taken out equal to $wanted ([[item, qty], ...]) from one store.
     * Called after each save of the document, so editing, paying or moving it keeps the stock right.
     */
    public static function syncIssue(Model $document, int $storeId, array $wanted, string $type = self::SALE): void
    {
        DB::transaction(function () use ($document, $storeId, $wanted, $type) {
            if (self::net($document)->contains(fn ($row) => (int) $row->store_id !== $storeId)) {
                self::reverse($document, self::RETURN);
            }
            $want = [];
            foreach ($wanted as [$item, $qty]) {
                $key = $item->getMorphClass().':'.$item->getKey();
                $want[$key] = [$item, ($want[$key][1] ?? 0) + (int) $qty];
            }
            $out = self::net($document)->mapWithKeys(fn ($row) => [$row->stockable_type.':'.$row->stockable_id => -(int) $row->net]);

            foreach ($out as $key => $qty) {
                if (! isset($want[$key]) && $qty > 0) {
                    [$class, $id] = explode(':', $key);
                    self::giveBack($class, (int) $id, $document, $qty);
                }
            }
            foreach ($want as $key => [$item, $qty]) {
                $diff = $qty - (int) ($out[$key] ?? 0);
                if ($diff > 0) {
                    self::issue($item, $storeId, $diff, $document, $type);
                } elseif ($diff < 0) {
                    self::giveBack($item->getMorphClass(), $item->getKey(), $document, -$diff);
                }
            }
        });
    }

    /** Earliest expiry among the batches a document still has an item out of (shown on the sale line). */
    public static function expiryOut(Model $document, Model $item): ?string
    {
        $batchIds = StockMovement::forDocument($document)->where('stockable_type', $item->getMorphClass())->where('stockable_id', $item->getKey())
            ->selectRaw('stock_batch_id, SUM(quantity) as net')->groupBy('stock_batch_id')->get()
            ->filter(fn ($row) => (int) $row->net < 0)->pluck('stock_batch_id');

        return $batchIds->isEmpty() ? null : StockBatch::whereIn('id', $batchIds)->min('expiry_date');
    }

    /** Expiry of the batch the next issue would take, for forms that show it in advance. */
    public static function nextExpiry(Model $item, ?int $storeId): ?string
    {
        if (! $storeId) {
            return null;
        }
        $query = self::batches($item->getMorphClass(), $item->getKey(), $storeId)->where('quantity', '>', 0)->whereNotNull('expiry_date');
        if (! self::sellExpired()) {
            $query->where('expiry_date', '>=', now()->toDateString());
        }

        return self::fefo() ? $query->min('expiry_date') : $query->orderBy('id')->value('expiry_date');
    }

    /** Return goods to the batches the document took them from, latest first. */
    private static function giveBack(string $class, int $id, Model $document, int $qty): void
    {
        $taken = StockMovement::forDocument($document)->where('stockable_type', $class)->where('stockable_id', $id)
            ->selectRaw('stock_batch_id, SUM(quantity) as net')->groupBy('stock_batch_id')->orderByDesc('stock_batch_id')->get();
        foreach ($taken as $row) {
            $give = min($qty, -(int) $row->net);
            if ($give <= 0) {
                continue;
            }
            $batch = StockBatch::lockForUpdate()->find($row->stock_batch_id);
            $batch->increment('quantity', $give);
            self::log($batch, $give, self::RETURN, $document);
            $qty -= $give;
            if ($qty <= 0) {
                break;
            }
        }
    }

    /** Undo everything a document moved. Goods received and already used elsewhere block it. */
    public static function reverse(Model $document, string $type = self::REVERSAL): void
    {
        DB::transaction(function () use ($document, $type) {
            $rows = StockMovement::forDocument($document)->selectRaw('stock_batch_id, SUM(quantity) as net')->groupBy('stock_batch_id')->get();
            foreach ($rows as $row) {
                $net = (int) $row->net;
                if ($net === 0) {
                    continue;
                }
                $batch = StockBatch::lockForUpdate()->find($row->stock_batch_id);
                if ($net > 0 && $batch->quantity < $net && ! self::allowNegative()) {
                    throw new UnprocessableEntityHttpException(__('messages.stock.already_used', ['item' => self::name($batch->stockable_type, $batch->stockable_id)]));
                }
                $batch->decrement('quantity', $net);
                self::log($batch, -$net, $type, $document);
            }
        });
    }

    private static function log(StockBatch $batch, int $qty, string $type, Model $document, ?string $note = null, ?float $cost = null): StockMovement
    {
        $move = StockMovement::create(['store_id' => $batch->store_id, 'stock_batch_id' => $batch->id, 'stockable_type' => $batch->stockable_type,
            'stockable_id' => $batch->stockable_id, 'quantity' => $qty, 'unit_cost' => $cost ?? $batch->unit_cost, 'movement_type' => $type,
            'document_type' => $document->getMorphClass(), 'document_id' => $document->getKey(), 'user_id' => auth()->id(), 'note' => $note]);
        self::refreshTotal($batch->stockable_type, $batch->stockable_id);

        return $move;
    }

    /** Cached total of all stores, shown in the item lists. */
    public static function refreshTotal(string $type, int $id): void
    {
        if ($table = self::TOTALS[$type] ?? null) {
            DB::table($table)->where('id', $id)->update(['available_quantity' => (int) StockBatch::where('stockable_type', $type)->where('stockable_id', $id)->sum('quantity')]);
        }
    }

    private static function name(string $type, int $id): string
    {
        $table = self::TOTALS[$type] ?? null;

        return $table ? (string) DB::table($table)->where('id', $id)->value('name') : '#'.$id;
    }
}
