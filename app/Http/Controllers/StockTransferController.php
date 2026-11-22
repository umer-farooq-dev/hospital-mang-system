<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Requisitions, transfers and returns between stores, and returns to suppliers. */
class StockTransferController extends AppBaseController
{
    public function index(Request $request)
    {
        $stores = Store::options();
        $status = in_array($request->input('status'), StockTransfer::STATUSES, true) ? $request->input('status') : null;
        $storeId = (int) $request->input('store_id');
        $ids = $storeId && isset($stores[$storeId]) ? [$storeId] : array_keys($stores);
        $transfers = StockTransfer::with('fromStore', 'toStore', 'supplier')
            ->where(fn ($q) => $q->whereIn('from_store_id', $ids)->orWhereIn('to_store_id', $ids))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('stock_transfers.index', compact('transfers', 'stores', 'status', 'storeId'));
    }

    public function create(Request $request)
    {
        $kind = in_array($request->input('kind'), StockTransfer::KINDS, true) ? $request->input('kind') : 'requisition';

        return view('stock_transfers.create', [
            'kind' => $kind,
            'myStores' => Store::options(),
            'allStores' => Store::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all(),
            'suppliers' => Supplier::options(),
            'medicines' => Medicine::orderBy('name')->get(['id', 'name', 'available_quantity'])
                ->mapWithKeys(fn ($m) => [$m->id => $m->name.' ('.$m->available_quantity.')'])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $mine = array_keys(Store::options());
        $active = Store::where('is_active', true)->pluck('id')->all();
        $kind = $request->input('kind');
        $data = $request->validate([
            'kind' => ['required', Rule::in(StockTransfer::KINDS)],
            // A requisition is made by the store that needs the goods; the others by the store that sends them.
            'from_store_id' => ['required', Rule::in($kind === 'requisition' ? $active : $mine)],
            'to_store_id' => $kind === 'supplier_return' ? ['nullable'] : ['required', 'different:from_store_id', Rule::in($kind === 'requisition' ? $mine : $active)],
            'supplier_id' => $kind === 'supplier_return' ? ['required', 'exists:suppliers,id'] : ['nullable'],
            'note' => ['nullable', 'string', 'max:500'],
            'medicine' => ['required', 'array'],
            'medicine.*' => ['nullable', 'integer', 'exists:medicines,id'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $lines = [];
        foreach ($data['medicine'] as $i => $id) {
            $qty = (int) ($data['quantity'][$i] ?? 0);
            if ($id && $qty > 0) {
                $lines[$id] = [Medicine::find($id), ($lines[$id][1] ?? 0) + $qty];
            }
        }
        if (! $lines) {
            return back()->withInput()->withErrors(['medicine' => __('messages.stock_transfer.no_lines')]);
        }
        $transfer = StockTransfer::open($data['kind'], (int) $data['from_store_id'], $data['to_store_id'] ?? null ? (int) $data['to_store_id'] : null,
            $data['supplier_id'] ?? null ? (int) $data['supplier_id'] : null, array_values($lines), $data['note'] ?? null, getLoggedInUserId());
        Flash::success(__('messages.stock_transfer.saved', ['number' => $transfer->number]));

        return redirect()->route('stock-transfers.show', $transfer->id);
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load('items.stockable', 'fromStore', 'toStore', 'supplier');
        $mine = Store::options();

        return view('stock_transfers.show', [
            'transfer' => $stockTransfer,
            'canSend' => isset($mine[$stockTransfer->from_store_id]),
            'canReceive' => $stockTransfer->to_store_id && isset($mine[$stockTransfer->to_store_id]),
        ]);
    }

    public function approveTransfer(Request $request, StockTransfer $stockTransfer)
    {
        return $this->step($stockTransfer, fn () => $stockTransfer->approve(array_map('intval', (array) $request->input('quantity', [])), getLoggedInUserId()));
    }

    public function send(Request $request, StockTransfer $stockTransfer)
    {
        abort_unless(isset(Store::options()[$stockTransfer->from_store_id]), 403);

        return $this->step($stockTransfer, fn () => $stockTransfer->send(array_map('intval', (array) $request->input('quantity', [])), getLoggedInUserId()));
    }

    public function receive(StockTransfer $stockTransfer)
    {
        abort_unless($stockTransfer->to_store_id && isset(Store::options()[$stockTransfer->to_store_id]), 403);

        return $this->step($stockTransfer, fn () => $stockTransfer->receive(getLoggedInUserId()));
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        $mine = Store::options();
        abort_unless(isset($mine[$stockTransfer->from_store_id]) || isset($mine[$stockTransfer->to_store_id]) || (int) $stockTransfer->requested_by === (int) getLoggedInUserId(), 403);

        return $this->step($stockTransfer, fn () => $stockTransfer->cancel(getLoggedInUserId()));
    }

    private function step(StockTransfer $transfer, callable $action)
    {
        try {
            $action();
            Flash::success(__('messages.stock_transfer.done'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('stock-transfers.show', $transfer->id);
    }
}
