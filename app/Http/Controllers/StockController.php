<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Store;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Stock on hand by store and batch, and the ledger of one item. */
class StockController extends AppBaseController
{
    const FILTERS = ['in_stock', 'expiring', 'expired', 'all'];

    // What the stock page can list: kind => [model, table].
    const KINDS = ['medicines' => [Medicine::class, 'medicines'], 'items' => [\App\Models\Item::class, 'items']];

    public function index(Request $request)
    {
        $stores = Store::options();
        $storeId = (int) $request->input('store_id');
        $filter = in_array($request->input('filter'), self::FILTERS, true) ? $request->input('filter') : 'in_stock';
        $search = trim((string) $request->input('search'));
        $alertDays = Stock::expiryAlertDays();
        $today = now()->toDateString();
        $kind = isset(self::KINDS[$request->input('kind')]) ? $request->input('kind') : 'medicines';
        [$class, $table] = self::KINDS[$kind];

        $query = StockBatch::query()->join($table.' as medicines', 'medicines.id', '=', 'stock_batches.stockable_id')
            ->where('stock_batches.stockable_type', $class)
            ->whereIn('stock_batches.store_id', $storeId && isset($stores[$storeId]) ? [$storeId] : array_keys($stores))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('medicines.name', 'like', "%{$search}%")
                ->when($kind === 'medicines', fn ($q) => $q->orWhere('medicines.common_name', 'like', "%{$search}%")->orWhere('medicines.barcode', $search))
                ->orWhere('stock_batches.batch_no', $search)))
            ->when($filter === 'in_stock', fn ($q) => $q->where('stock_batches.quantity', '!=', 0))
            ->when($filter === 'expiring', fn ($q) => $q->where('stock_batches.quantity', '>', 0)
                ->whereBetween('stock_batches.expiry_date', [$today, now()->addDays($alertDays)->toDateString()]))
            ->when($filter === 'expired', fn ($q) => $q->where('stock_batches.quantity', '>', 0)->where('stock_batches.expiry_date', '<', $today));

        $value = (clone $query)->sum(DB::raw('stock_batches.quantity * stock_batches.unit_cost'));
        $batches = $query->with('store')->select('stock_batches.*', 'medicines.name as item_name')
            ->orderBy('medicines.name')->orderByRaw('stock_batches.expiry_date IS NULL')->orderBy('stock_batches.expiry_date')
            ->paginate(50)->withQueryString();

        return view('stock.index', compact('stores', 'storeId', 'filter', 'search', 'batches', 'value', 'alertDays', 'kind'));
    }

    public function movements(Request $request, Medicine $medicine)
    {
        return $this->ledger($request, $medicine);
    }

    public function itemMovements(Request $request, \App\Models\Item $item)
    {
        return $this->ledger($request, $item);
    }

    /** Ledger of one medicine or item (optionally one store) with the running balance. */
    private function ledger(Request $request, \Illuminate\Database\Eloquent\Model $medicine)
    {
        $stores = Store::options();
        $storeId = (int) $request->input('store_id');
        $query = StockMovement::where('stockable_type', $medicine->getMorphClass())->where('stockable_id', $medicine->id)
            ->whereIn('store_id', $storeId && isset($stores[$storeId]) ? [$storeId] : array_keys($stores));
        // ponytail: the last 300 lines with the balance before them; add paging if one item's ledger often gets longer.
        $moves = (clone $query)->with('store', 'batch', 'user', 'document')->orderByDesc('id')->limit(300)->get()->reverse()->values();
        $balance = $moves->isEmpty() ? 0 : (int) (clone $query)->where('id', '<', $moves->first()->id)->sum('quantity');

        return view('stock.movements', compact('medicine', 'stores', 'storeId', 'moves', 'balance'));
    }
}
