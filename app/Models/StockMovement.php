<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One line of the stock ledger: + in, - out, on a batch of a store, caused by a document. */
class StockMovement extends Model
{
    protected $fillable = ['store_id', 'stock_batch_id', 'stockable_type', 'stockable_id', 'quantity', 'unit_cost', 'movement_type',
        'document_type', 'document_id', 'user_id', 'note'];

    protected $casts = ['quantity' => 'integer', 'unit_cost' => 'float'];

    // Document type => [number column, show route, route parameter]
    const DOCUMENTS = [
        PurchaseMedicine::class => ['purchase_no', 'medicine-purchase.show', 'medicine_purchase'],
        MedicineBill::class => ['bill_number', 'medicine-bills.show', 'medicine_bill'],
        StockTransfer::class => ['number', 'stock-transfers.show', 'stockTransfer'],
        StockCount::class => ['number', 'stock-counts.show', 'stockCount'],
        ItemStock::class => ['id', 'item.stock.show', 'itemStock'],
        IssuedItem::class => ['id', 'issued.item.show', 'issuedItem'],
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForDocument(Builder $query, Model $document): Builder
    {
        return $query->where('document_type', $document->getMorphClass())->where('document_id', $document->getKey());
    }

    /** [label, url or null] of the document behind this movement. */
    public function documentLink(): array
    {
        [$column, $route, $parameter] = self::DOCUMENTS[$this->document_type] ?? [null, null, null];
        if (! $column) {
            return [$this->movement_type === 'opening' ? __('messages.stock.opening') : class_basename((string) $this->document_type), null];
        }
        $number = $this->document?->{$column};

        return [$number ?? '#'.$this->document_id, $number && canAccessRoute($route) ? route($route, [$parameter => $this->document_id]) : null];
    }
}
