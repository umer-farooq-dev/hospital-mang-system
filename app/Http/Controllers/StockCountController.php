<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockCount;
use App\Models\Store;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Stock counts per store: count sheet, variance, approval and adjustment. */
class StockCountController extends AppBaseController
{
    const FOUND_ROWS = 5;

    public function index(Request $request)
    {
        $stores = Store::options();
        $counts = StockCount::with('store')->whereIn('store_id', array_keys($stores))
            ->when(in_array($request->input('status'), StockCount::STATUSES, true), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('stock_counts.index', ['counts' => $counts, 'stores' => $stores, 'status' => $request->input('status'),
            'blindDefault' => Stock::setting('stock_count_blind', '0') === '1']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', Rule::in(array_keys(Store::options()))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $count = StockCount::open((int) $data['store_id'], $request->boolean('blind'), $data['note'] ?? null, getLoggedInUserId());
        Flash::success(__('messages.stock_count.created', ['number' => $count->number, 'lines' => $count->lines()->count()]));

        return redirect()->route('stock-counts.show', $count->id);
    }

    public function show(StockCount $stockCount)
    {
        $stockCount->load('store', 'lines.stockable');
        $lines = $stockCount->lines->sortBy(fn ($l) => mb_strtolower(($l->stockable->name ?? '').' '.$l->expiry_date))->values();

        return view('stock_counts.show', [
            'count' => $stockCount,
            'lines' => $lines,
            'canCount' => $stockCount->status === StockCount::DRAFT && isset(Store::options()[$stockCount->store_id]) && canAccessRoute('stock-counts.update'),
            'medicines' => Medicine::orderBy('name')->pluck('name', 'id')->all(),
            'foundRows' => self::FOUND_ROWS,
        ]);
    }

    /** Save the counted quantities; with "submit" the sheet goes for approval. */
    public function update(Request $request, StockCount $stockCount)
    {
        abort_unless(isset(Store::options()[$stockCount->store_id]), 403);
        $request->validate([
            'counted' => ['nullable', 'array'],
            'counted.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'found' => ['nullable', 'array'],
            'found.*.medicine' => ['nullable', 'integer', 'exists:medicines,id'],
            'found.*.batch_no' => ['nullable', 'string', 'max:100'],
            'found.*.expiry_date' => ['nullable', 'date'],
            'found.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $found = [];
        foreach ((array) $request->input('found', []) as $row) {
            if (! empty($row['medicine']) && (int) ($row['quantity'] ?? 0) > 0) {
                $found[] = [Medicine::find($row['medicine']), $row['batch_no'] ?? null, $row['expiry_date'] ?? null, (int) $row['quantity']];
            }
        }

        return $this->step($stockCount, function () use ($request, $stockCount, $found) {
            $stockCount->record((array) $request->input('counted', []), $found);
            if ($request->boolean('submit')) {
                $stockCount->submit(getLoggedInUserId());
            }
        });
    }

    public function approveCount(StockCount $stockCount)
    {
        return $this->step($stockCount, fn () => $stockCount->approve(getLoggedInUserId()));
    }

    public function cancel(StockCount $stockCount)
    {
        abort_unless(isset(Store::options()[$stockCount->store_id]), 403);

        return $this->step($stockCount, fn () => $stockCount->cancel(getLoggedInUserId()));
    }

    private function step(StockCount $count, callable $action)
    {
        try {
            $action();
            Flash::success(__('messages.stock_count.saved'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('stock-counts.show', $count->id);
    }
}
