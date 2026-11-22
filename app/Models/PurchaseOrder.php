<?php

namespace App\Models;

use App\Utils\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Order to a supplier for one store: ordered -> partial -> received, or closed (rest will not come) / cancelled.
 * Each delivery is a goods receipt (Purchase Medicine) linked to the order, which puts the lots into the store.
 */
class PurchaseOrder extends Model
{
    const ORDERED = 'ordered';

    const PARTIAL = 'partial';

    const RECEIVED = 'received';

    const CLOSED = 'closed';

    const CANCELLED = 'cancelled';

    const STATUSES = [self::ORDERED, self::PARTIAL, self::RECEIVED, self::CLOSED, self::CANCELLED];

    protected $fillable = ['number', 'supplier_id', 'store_id', 'status', 'expected_date', 'note', 'created_by', 'closed_by', 'closed_at', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['closed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseMedicine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stepUser(string $step): BelongsTo
    {
        return $this->belongsTo(User::class, $step.'_by');
    }

    public function total(): float
    {
        return round($this->items->sum(fn ($line) => $line->quantity * $line->unit_cost), 2);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::ORDERED, self::PARTIAL], true);
    }

    public static function newNumber(): string
    {
        $prefix = trim((string) Stock::setting('purchase_order_prefix', 'PO')) ?: 'PO';
        $year = (int) now()->format('Y');
        $last = self::where('number', 'like', $prefix.'-'.$year.'-%')->orderByDesc('id')->value('number');

        return Certificate::nextNumber($prefix, $year, $last);
    }

    /** @param  array  $lines  [[item, qty, unit cost], ...] */
    public static function open(int $supplierId, int $storeId, array $lines, ?string $expected, ?string $note, int $userId): self
    {
        return DB::transaction(function () use ($supplierId, $storeId, $lines, $expected, $note, $userId) {
            $order = self::create(['number' => self::newNumber(), 'supplier_id' => $supplierId, 'store_id' => $storeId, 'status' => self::ORDERED,
                'expected_date' => $expected ?: null, 'note' => $note, 'created_by' => $userId]);
            foreach ($lines as [$item, $qty, $cost]) {
                $order->items()->create(['stockable_type' => $item->getMorphClass(), 'stockable_id' => $item->getKey(),
                    'quantity' => (int) $qty, 'unit_cost' => round((float) $cost, 2)]);
            }

            return $order;
        });
    }

    /** Quantity still to come per item key "Class:id" in each store, from open orders. */
    public static function onOrder(int $storeId): array
    {
        return PurchaseOrderItem::whereHas('order', fn ($q) => $q->where('store_id', $storeId)->whereIn('status', [self::ORDERED, self::PARTIAL]))
            ->get()->groupBy(fn ($line) => $line->stockable_type.':'.$line->stockable_id)
            ->map(fn ($lines) => (int) $lines->sum(fn ($line) => $line->remaining()))->all();
    }

    private function claim(array $from): void
    {
        $fresh = self::lockForUpdate()->findOrFail($this->id);
        if (! in_array($fresh->status, $from, true)) {
            throw new UnprocessableEntityHttpException(__('messages.purchase_order.wrong_status'));
        }
    }

    /**
     * Record a delivery: a goods receipt with one line per lot received.
     *
     * @param  array  $deliveries  [[order item, qty, lot, expiry, unit cost], ...]
     */
    public function receive(array $deliveries, int $paymentType, bool $paid, ?string $note): PurchaseMedicine
    {
        return DB::transaction(function () use ($deliveries, $paymentType, $paid, $note) {
            $this->claim([self::ORDERED, self::PARTIAL]);
            $deliveries = array_values(array_filter($deliveries, fn ($d) => (int) $d[1] > 0));
            if (! $deliveries) {
                throw new UnprocessableEntityHttpException(__('messages.purchase_order.nothing_received'));
            }
            $total = round(array_sum(array_map(fn ($d) => (int) $d[1] * (float) $d[4], $deliveries)), 2);
            $receipt = PurchaseMedicine::create(['purchase_no' => generateUniquePurchaseNumber(), 'store_id' => $this->store_id,
                'supplier_id' => $this->supplier_id, 'purchase_order_id' => $this->id, 'total' => $total, 'discount' => 0, 'tax' => 0,
                'net_amount' => $total, 'payment_type' => $paymentType, 'payment_status' => $paid ? 1 : 0, 'note' => $note ?: $this->number]);
            foreach ($deliveries as [$line, $qty, $lot, $expiry, $cost]) {
                if ((int) $line->purchase_order_id !== (int) $this->id) {
                    throw new UnprocessableEntityHttpException(__('messages.purchase_order.wrong_status'));
                }
                // Lines are received by the PurchasedMedicine created event (lot into the store).
                PurchasedMedicine::create(['purchase_medicines_id' => $receipt->id, 'medicine_id' => $line->stockable_id, 'lot_no' => $lot,
                    'expiry_date' => $expiry ?: null, 'quantity' => (int) $qty, 'amount' => round((int) $qty * (float) $cost, 2), 'tax' => 0]);
                $line->increment('received_quantity', (int) $qty);
            }
            $this->load('items');
            $done = $this->items->every(fn ($line) => $line->received_quantity >= $line->quantity);
            $this->update(['status' => $done ? self::RECEIVED : self::PARTIAL]);

            return $receipt;
        });
    }

    /** The rest of a partly delivered order will not come. */
    public function close(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::PARTIAL]);
            $this->update(['status' => self::CLOSED, 'closed_by' => $userId, 'closed_at' => now()]);
        });
    }

    public function cancel(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::ORDERED]);
            $this->update(['status' => self::CANCELLED, 'cancelled_by' => $userId, 'cancelled_at' => now()]);
        });
    }
}
