<?php

namespace App\Models;

use App\Utils\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Goods moving between stores (requisition, transfer, return) or back to a supplier.
 * requested -> approved -> sent (stock leaves the sending store) -> received (stock enters the receiving store, same batches).
 * A return to a supplier ends at sent. Cancelling a sent document puts the stock back.
 */
class StockTransfer extends Model
{
    const KINDS = ['requisition', 'transfer', 'return', 'supplier_return'];

    const REQUESTED = 'requested';

    const APPROVED = 'approved';

    const SENT = 'sent';

    const RECEIVED = 'received';

    const CANCELLED = 'cancelled';

    const STATUSES = [self::REQUESTED, self::APPROVED, self::SENT, self::RECEIVED, self::CANCELLED];

    protected $fillable = ['number', 'kind', 'status', 'from_store_id', 'to_store_id', 'supplier_id', 'note',
        'requested_by', 'requested_at', 'approved_by', 'approved_at', 'sent_by', 'sent_at', 'received_by', 'received_at', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'sent_at' => 'datetime', 'received_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stepUser(string $step): BelongsTo
    {
        return $this->belongsTo(User::class, $step.'_by');
    }

    public function isSupplierReturn(): bool
    {
        return $this->kind === 'supplier_return';
    }

    public static function approvalRequired(): bool
    {
        return Stock::setting('stock_transfer_approval', '1') === '1';
    }

    public static function newNumber(): string
    {
        $prefix = trim((string) Stock::setting('stock_transfer_prefix', 'TR')) ?: 'TR';
        $year = (int) now()->format('Y');
        $last = self::where('number', 'like', $prefix.'-'.$year.'-%')->orderByDesc('id')->value('number');

        return Certificate::nextNumber($prefix, $year, $last);
    }

    /**
     * New document with its lines ([[item, qty], ...]). Without approval in the settings it starts approved.
     */
    public static function open(string $kind, ?int $fromStoreId, ?int $toStoreId, ?int $supplierId, array $lines, ?string $note, int $userId): self
    {
        return DB::transaction(function () use ($kind, $fromStoreId, $toStoreId, $supplierId, $lines, $note, $userId) {
            $approved = ! self::approvalRequired();
            $transfer = self::create(['number' => self::newNumber(), 'kind' => $kind, 'status' => $approved ? self::APPROVED : self::REQUESTED,
                'from_store_id' => $fromStoreId, 'to_store_id' => $kind === 'supplier_return' ? null : $toStoreId,
                'supplier_id' => $kind === 'supplier_return' ? $supplierId : null, 'note' => $note,
                'requested_by' => $userId, 'requested_at' => now()] + ($approved ? ['approved_by' => $userId, 'approved_at' => now()] : []));
            foreach ($lines as [$item, $qty]) {
                $transfer->items()->create(['stockable_type' => $item->getMorphClass(), 'stockable_id' => $item->getKey(),
                    'quantity_requested' => (int) $qty, 'quantity_approved' => $approved ? (int) $qty : null]);
            }

            return $transfer;
        });
    }

    /** Lock the row and check it is still at the expected step (two users clicking at once). */
    private function claim(array $from): self
    {
        $fresh = self::lockForUpdate()->findOrFail($this->id);
        if (! in_array($fresh->status, $from, true)) {
            throw new UnprocessableEntityHttpException(__('messages.stock_transfer.wrong_status'));
        }

        return $fresh;
    }

    /** @param  array<int,int>  $quantities  item id => approved quantity (0 drops the line) */
    public function approve(array $quantities, int $userId): void
    {
        DB::transaction(function () use ($quantities, $userId) {
            $this->claim([self::REQUESTED]);
            foreach ($this->items as $line) {
                $line->update(['quantity_approved' => max(0, (int) ($quantities[$line->id] ?? $line->quantity_requested))]);
            }
            $this->update(['status' => self::APPROVED, 'approved_by' => $userId, 'approved_at' => now()]);
        });
    }

    /** @param  array<int,int>  $quantities  item id => quantity sent, at most the approved one */
    public function send(array $quantities, int $userId): void
    {
        DB::transaction(function () use ($quantities, $userId) {
            $this->claim([self::APPROVED]);
            $total = 0;
            foreach ($this->items()->with('stockable')->get() as $line) {
                $qty = min((int) $line->quantity_approved, max(0, (int) ($quantities[$line->id] ?? $line->quantity_approved)));
                if ($qty > 0) {
                    Stock::issue($line->stockable, (int) $this->from_store_id, $qty, $this, $this->isSupplierReturn() ? Stock::SUPPLIER_RETURN : Stock::TRANSFER_OUT);
                }
                $line->update(['quantity_sent' => $qty]);
                $total += $qty;
            }
            if ($total === 0) {
                throw new UnprocessableEntityHttpException(__('messages.stock_transfer.nothing_to_send'));
            }
            $this->update(['status' => self::SENT, 'sent_by' => $userId, 'sent_at' => now()]);
        });
    }

    /** The receiving store gets exactly the batches that left (same batch number, expiry and cost). */
    public function receive(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::SENT]);
            if ($this->isSupplierReturn()) {
                throw new UnprocessableEntityHttpException(__('messages.stock_transfer.wrong_status'));
            }
            $rows = StockMovement::forDocument($this)->where('store_id', $this->from_store_id)
                ->selectRaw('stock_batch_id, SUM(quantity) as net')->groupBy('stock_batch_id')->get();
            foreach ($rows as $row) {
                if ((int) $row->net >= 0) {
                    continue;
                }
                $batch = StockBatch::with('stockable')->find($row->stock_batch_id);
                Stock::receive($batch->stockable, (int) $this->to_store_id, -(int) $row->net, ['batch_no' => $batch->batch_no,
                    'expiry_date' => $batch->expiry_date, 'unit_cost' => $batch->unit_cost, 'supplier_id' => $batch->supplier_id], $this, Stock::TRANSFER_IN);
            }
            $this->update(['status' => self::RECEIVED, 'received_by' => $userId, 'received_at' => now()]);
        });
    }

    public function cancel(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::REQUESTED, self::APPROVED, self::SENT]);
            Stock::reverse($this);
            $this->update(['status' => self::CANCELLED, 'cancelled_by' => $userId, 'cancelled_at' => now()]);
        });
    }
}
