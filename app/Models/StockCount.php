<?php

namespace App\Models;

use App\Utils\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * A stock count of one store: draft (counting) -> submitted -> approved (stock adjusted) or cancelled.
 * The sheet keeps what the system held when it was made; the adjustment is counted minus that,
 * so sales made while counting are not lost.
 */
class StockCount extends Model
{
    const DRAFT = 'draft';

    const SUBMITTED = 'submitted';

    const APPROVED = 'approved';

    const CANCELLED = 'cancelled';

    const STATUSES = [self::DRAFT, self::SUBMITTED, self::APPROVED, self::CANCELLED];

    protected $fillable = ['number', 'store_id', 'status', 'blind', 'note', 'created_by', 'submitted_by', 'submitted_at',
        'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['blind' => 'boolean', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stepUser(string $step): BelongsTo
    {
        return $this->belongsTo(User::class, $step.'_by');
    }

    public static function newNumber(): string
    {
        $prefix = trim((string) Stock::setting('stock_count_prefix', 'SC')) ?: 'SC';
        $year = (int) now()->format('Y');
        $last = self::where('number', 'like', $prefix.'-'.$year.'-%')->orderByDesc('id')->value('number');

        return Certificate::nextNumber($prefix, $year, $last);
    }

    /** New count sheet with every batch the store holds (or holds below zero). */
    public static function open(int $storeId, bool $blind, ?string $note, int $userId): self
    {
        return DB::transaction(function () use ($storeId, $blind, $note, $userId) {
            $count = self::create(['number' => self::newNumber(), 'store_id' => $storeId, 'status' => self::DRAFT, 'blind' => $blind,
                'note' => $note, 'created_by' => $userId]);
            $batches = StockBatch::where('store_id', $storeId)->where('quantity', '!=', 0)->get();
            foreach ($batches as $batch) {
                $count->lines()->create(['stock_batch_id' => $batch->id, 'stockable_type' => $batch->stockable_type, 'stockable_id' => $batch->stockable_id,
                    'batch_no' => $batch->batch_no, 'expiry_date' => $batch->expiry_date, 'unit_cost' => $batch->unit_cost, 'system_quantity' => $batch->quantity]);
            }

            return $count;
        });
    }

    private function claim(array $from): void
    {
        $fresh = self::lockForUpdate()->findOrFail($this->id);
        if (! in_array($fresh->status, $from, true)) {
            throw new UnprocessableEntityHttpException(__('messages.stock_count.wrong_status'));
        }
    }

    /**
     * Save counted quantities (line id => qty, empty = not counted) and goods found that the sheet does not list
     * ([[item, batch_no, expiry, qty], ...]).
     */
    public function record(array $counted, array $found): void
    {
        DB::transaction(function () use ($counted, $found) {
            $this->claim([self::DRAFT]);
            foreach ($this->lines as $line) {
                if (array_key_exists($line->id, $counted)) {
                    $value = $counted[$line->id];
                    $line->update(['counted_quantity' => $value === null || $value === '' ? null : max(0, (int) $value)]);
                }
            }
            foreach ($found as [$item, $batchNo, $expiry, $qty]) {
                $this->lines()->create(['stockable_type' => $item->getMorphClass(), 'stockable_id' => $item->getKey(), 'batch_no' => $batchNo ?: null,
                    'expiry_date' => $expiry ?: null, 'unit_cost' => (float) ($item->buying_price ?? 0), 'system_quantity' => 0, 'counted_quantity' => max(0, (int) $qty)]);
            }
        });
    }

    public function submit(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::DRAFT]);
            if ($this->lines()->whereNull('counted_quantity')->exists()) {
                throw new UnprocessableEntityHttpException(__('messages.stock_count.not_all_counted'));
            }
            $this->update(['status' => self::SUBMITTED, 'submitted_by' => $userId, 'submitted_at' => now()]);
        });
    }

    /** Approving posts one adjustment per line with a variance. */
    public function approve(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::SUBMITTED]);
            foreach ($this->lines()->with('stockable')->get() as $line) {
                $variance = (int) $line->variance();
                if ($variance === 0 || ! $line->stockable) {
                    continue;
                }
                $batch = $line->stock_batch_id ? StockBatch::find($line->stock_batch_id) : null;
                if ($batch) {
                    Stock::adjust($batch, $variance, $this);
                } else {
                    Stock::receive($line->stockable, (int) $this->store_id, $variance, ['batch_no' => $line->batch_no, 'expiry_date' => $line->expiry_date,
                        'unit_cost' => $line->unit_cost], $this, Stock::ADJUSTMENT);
                }
            }
            $this->update(['status' => self::APPROVED, 'approved_by' => $userId, 'approved_at' => now()]);
        });
    }

    public function cancel(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->claim([self::DRAFT, self::SUBMITTED]);
            $this->update(['status' => self::CANCELLED, 'cancelled_by' => $userId, 'cancelled_at' => now()]);
        });
    }
}
