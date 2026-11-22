<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\Supplier;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Replenishment: reorder levels, reorder report, purchase orders and their deliveries. */
class PurchaseOrderController extends AppBaseController
{
    public function index(Request $request)
    {
        $stores = Store::options();
        $status = in_array($request->input('status'), PurchaseOrder::STATUSES, true) ? $request->input('status') : null;
        $orders = PurchaseOrder::with('supplier', 'store', 'items')->whereIn('store_id', array_keys($stores))
            ->when($status, fn ($q) => $q->where('status', $status))->orderByDesc('id')->paginate(30)->withQueryString();

        return view('purchase_orders.index', compact('orders', 'status'));
    }

    public function create(Request $request)
    {
        return view('purchase_orders.create', [
            'stores' => Store::options(),
            'suppliers' => Supplier::options(),
            'medicines' => Medicine::orderBy('name')->pluck('name', 'id')->all(),
            'costs' => Medicine::pluck('buying_price', 'id')->all(),
            'lines' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'store_id' => ['required', Rule::in(array_keys(Store::options()))],
            'expected_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'medicine' => ['required', 'array'],
            'medicine.*' => ['nullable', 'integer', 'exists:medicines,id'],
            'quantity.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'unit_cost.*' => ['nullable', 'numeric', 'min:0'],
        ]);
        $lines = [];
        foreach ($data['medicine'] as $i => $id) {
            $qty = (int) ($request->input('quantity')[$i] ?? 0);
            if ($id && $qty > 0) {
                $lines[] = [Medicine::find($id), $qty, (float) ($request->input('unit_cost')[$i] ?? 0)];
            }
        }
        if (! $lines) {
            return back()->withInput()->withErrors(['medicine' => __('messages.stock_transfer.no_lines')]);
        }
        $order = PurchaseOrder::open((int) $data['supplier_id'], (int) $data['store_id'], $lines, $data['expected_date'] ?? null, $data['note'] ?? null, getLoggedInUserId());
        Flash::success(__('messages.purchase_order.saved', ['number' => $order->number]));

        return redirect()->route('purchase-orders.show', $order->id);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.stockable', 'supplier', 'store', 'receipts');

        return view('purchase_orders.show', [
            'order' => $purchaseOrder,
            'canReceive' => $purchaseOrder->isOpen() && isset(Store::options()[$purchaseOrder->store_id]) && canAccessRoute('purchase-orders.receive'),
        ]);
    }

    public function storeReceipt(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless(isset(Store::options()[$purchaseOrder->store_id]), 403);
        $request->validate([
            'payment_type' => ['required', 'integer', Rule::in([0, 1])],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'lines.*.lot_no' => ['nullable', 'required_with:lines.*.quantity', 'regex:/^[A-Za-z0-9_#\-]+$/', 'max:100'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
        $items = $purchaseOrder->items->keyBy('id');
        $deliveries = [];
        foreach ((array) $request->input('lines') as $id => $row) {
            $line = $items[$id] ?? null;
            $qty = (int) ($row['quantity'] ?? 0);
            if ($line && $qty > 0) {
                $deliveries[] = [$line, $qty, $row['lot_no'], $row['expiry_date'] ?? null, (float) ($row['unit_cost'] ?? $line->unit_cost)];
            }
        }
        try {
            $receipt = $purchaseOrder->receive($deliveries, (int) $request->input('payment_type'), $request->boolean('paid'), $request->input('note'));
            Flash::success(__('messages.purchase_order.received', ['number' => $receipt->purchase_no]));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder->id);
    }

    public function close(PurchaseOrder $purchaseOrder)
    {
        return $this->step($purchaseOrder, fn () => $purchaseOrder->close(getLoggedInUserId()));
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        return $this->step($purchaseOrder, fn () => $purchaseOrder->cancel(getLoggedInUserId()));
    }

    /** Items at or below their reorder level in a store, with what is already on order. */
    public function reorder(Request $request)
    {
        $stores = Store::options();
        $storeId = (int) ($request->input('store_id') ?: Stock::defaultStoreId());
        abort_unless(isset($stores[$storeId]), 403);
        $levels = StockLevel::where('store_id', $storeId)->where('stockable_type', Medicine::class)->where('reorder_level', '>', 0)->get();
        $onHand = StockBatch::where('store_id', $storeId)->where('stockable_type', Medicine::class)
            ->selectRaw('stockable_id, SUM(quantity) as qty')->groupBy('stockable_id')->pluck('qty', 'stockable_id');
        $onOrder = PurchaseOrder::onOrder($storeId);
        $medicines = Medicine::whereIn('id', $levels->pluck('stockable_id'))->get()->keyBy('id');
        $rows = $levels->map(function ($level) use ($onHand, $onOrder, $medicines) {
            $have = (int) ($onHand[$level->stockable_id] ?? 0);
            $coming = (int) ($onOrder[Medicine::class.':'.$level->stockable_id] ?? 0);

            return ['medicine' => $medicines[$level->stockable_id] ?? null, 'level' => $level->reorder_level, 'on_hand' => $have, 'on_order' => $coming,
                'suggested' => StockLevel::suggested($level->reorder_level, $level->reorder_quantity, $have, $coming)];
        })->filter(fn ($row) => $row['medicine'] && $row['on_hand'] <= $row['level'])->sortBy(fn ($row) => $row['medicine']->name)->values();

        return view('purchase_orders.reorder', ['stores' => $stores, 'storeId' => $storeId, 'rows' => $rows, 'suppliers' => Supplier::options()]);
    }

    /** Reorder level and quantity of each medicine in one store. */
    public function levels(Request $request)
    {
        $stores = Store::options();
        $storeId = (int) ($request->input('store_id') ?: Stock::defaultStoreId());
        abort_unless(isset($stores[$storeId]), 403);
        $search = trim((string) $request->input('search'));
        $medicines = Medicine::when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))->orderBy('name')->paginate(50)->withQueryString();
        $levels = StockLevel::where('store_id', $storeId)->where('stockable_type', Medicine::class)
            ->whereIn('stockable_id', $medicines->pluck('id'))->get()->keyBy('stockable_id');
        $onHand = StockBatch::where('store_id', $storeId)->where('stockable_type', Medicine::class)->whereIn('stockable_id', $medicines->pluck('id'))
            ->selectRaw('stockable_id, SUM(quantity) as qty')->groupBy('stockable_id')->pluck('qty', 'stockable_id');

        return view('purchase_orders.levels', compact('stores', 'storeId', 'search', 'medicines', 'levels', 'onHand'));
    }

    public function updateLevels(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', Rule::in(array_keys(Store::options()))],
            'levels' => ['nullable', 'array'],
            'levels.*.reorder_level' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'levels.*.reorder_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        foreach ((array) ($data['levels'] ?? []) as $medicineId => $row) {
            if (! Medicine::whereKey($medicineId)->exists()) {
                continue;
            }
            StockLevel::updateOrCreate(['store_id' => $data['store_id'], 'stockable_type' => Medicine::class, 'stockable_id' => (int) $medicineId],
                ['reorder_level' => (int) ($row['reorder_level'] ?? 0), 'reorder_quantity' => (int) ($row['reorder_quantity'] ?? 0)]);
        }
        Flash::success(__('messages.stock.saved'));

        return back();
    }

    private function step(PurchaseOrder $order, callable $action)
    {
        try {
            $action();
            Flash::success(__('messages.stock_transfer.done'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('purchase-orders.show', $order->id);
    }
}
