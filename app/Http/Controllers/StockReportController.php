<?php

namespace App\Http\Controllers;

use App\Models\IssuedItem;
use App\Models\MedicineBill;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Stock reports: products issued, movements, expiry, valuation and slow-moving stock, on screen or as CSV. */
class StockReportController extends AppBaseController
{
    const REPORTS = ['issued', 'movements', 'expiry', 'valuation', 'slow'];

    const GROUPS = ['issued' => ['item', 'document', 'patient', 'department'], 'valuation' => ['store', 'item']];

    // Documents whose movements take stock out for use (the receiving side of a transfer is left out).
    const OUTFLOW_DOCUMENTS = [MedicineBill::class, IssuedItem::class, StockTransfer::class];

    // ponytail: the screen shows the first rows; the CSV export has them all.
    const SCREEN_ROWS = 500;

    public function index(Request $request)
    {
        $report = $this->build($request);

        return view('stock_reports.index', $report + [
            'stores' => Store::options(),
            'shown' => $request->attributes->get('exporting') ? $report['rows'] : array_slice($report['rows'], 0, self::SCREEN_ROWS),
        ]);
    }

    public function exportReport(Request $request)
    {
        $report = $this->build($request);
        $name = 'stock-'.$report['report'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel reads the accents
            fputcsv($out, $report['head'], ';');
            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map(fn ($cell) => is_float($cell) ? number_format($cell, 2, ',', '') : $cell, $row), ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{report: string, filters: array, head: string[], rows: array, numeric: int[], total: ?array} */
    private function build(Request $request): array
    {
        $report = in_array($request->input('report'), self::REPORTS, true) ? $request->input('report') : 'issued';
        $stores = Store::options();
        $storeId = (int) $request->input('store_id');
        $storeIds = $storeId && isset($stores[$storeId]) ? [$storeId] : array_keys($stores);
        $kind = isset(StockController::KINDS[$request->input('kind')]) ? $request->input('kind') : 'medicines';
        [$class, $table] = StockController::KINDS[$kind];
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($request->input('to') ?: now())->endOfDay();
        $days = max(0, (int) ($request->input('days') ?: ($report === 'expiry' ? Stock::expiryAlertDays() : 90)));
        $groups = self::GROUPS[$report] ?? [];
        $group = in_array($request->input('group'), $groups, true) ? $request->input('group') : ($groups[0] ?? null);
        $filters = compact('storeId', 'kind', 'from', 'to', 'days', 'group', 'groups');
        $names = fn (array $ids) => DB::table($table)->whereIn('id', $ids)->pluck('name', 'id');
        $storeNames = Store::pluck('name', 'id');
        $t = fn ($key) => __('messages.stock_report.'.$key);

        switch ($report) {
            case 'issued':
                $base = StockMovement::where('stock_movements.stockable_type', $class)->whereIn('stock_movements.store_id', $storeIds)
                    ->whereBetween('stock_movements.created_at', [$from, $to])->whereIn('stock_movements.document_type', self::OUTFLOW_DOCUMENTS)
                    ->where('stock_movements.movement_type', '!=', Stock::TRANSFER_IN);
                $select = 'SUM(-stock_movements.quantity) as qty, SUM(-stock_movements.quantity * stock_movements.unit_cost) as value';
                if ($group === 'patient') {
                    $data = $base->where('stock_movements.document_type', MedicineBill::class)
                        ->join('medicine_bills', 'medicine_bills.id', '=', 'stock_movements.document_id')
                        ->join('patients', 'patients.id', '=', 'medicine_bills.patient_id')->join('users', 'users.id', '=', 'patients.user_id')
                        ->selectRaw("patients.patient_unique_id as code, CONCAT(users.first_name, ' ', users.last_name) as label, ".$select)
                        ->groupBy('patients.patient_unique_id', 'users.first_name', 'users.last_name')->orderByDesc('value')->get();
                    $rows = $data->map(fn ($r) => [$r->code, $r->label, (int) $r->qty, round((float) $r->value, 2)])->all();
                    $head = [$t('patient_number'), $t('patient'), $t('quantity'), $t('value')];
                } elseif ($group === 'department') {
                    $data = $base->where('stock_movements.document_type', IssuedItem::class)
                        ->join('issued_items', 'issued_items.id', '=', 'stock_movements.document_id')
                        ->join('departments', 'departments.id', '=', 'issued_items.department_id')
                        ->selectRaw('departments.name as label, '.$select)->groupBy('departments.name')->orderByDesc('value')->get();
                    $rows = $data->map(fn ($r) => [$r->label, (int) $r->qty, round((float) $r->value, 2)])->all();
                    $head = [$t('department'), $t('quantity'), $t('value')];
                } elseif ($group === 'document') {
                    $data = $base->selectRaw('stock_movements.document_type as label, '.$select)->groupBy('stock_movements.document_type')->get();
                    $rows = $data->map(fn ($r) => [$t('documents.'.class_basename($r->label)), (int) $r->qty, round((float) $r->value, 2)])->all();
                    $head = [$t('document'), $t('quantity'), $t('value')];
                } else {
                    $data = $base->selectRaw('stock_movements.stockable_id as id, '.$select)->groupBy('stock_movements.stockable_id')->get();
                    $n = $names($data->pluck('id')->all());
                    $rows = $data->map(fn ($r) => [$n[$r->id] ?? '#'.$r->id, (int) $r->qty, round((float) $r->value, 2)])
                        ->sortByDesc(fn ($row) => $row[2])->values()->all();
                    $head = [$t('item'), $t('quantity'), $t('value')];
                }
                break;

            case 'movements':
                $moves = StockMovement::with('store', 'batch', 'user', 'document')->where('stockable_type', $class)->whereIn('store_id', $storeIds)
                    ->whereBetween('created_at', [$from, $to])->orderBy('id')->get();
                $n = $names($moves->pluck('stockable_id')->unique()->all());
                $rows = $moves->map(fn ($m) => [$m->created_at->format('d/m/Y H:i'), $storeNames[$m->store_id] ?? '', $n[$m->stockable_id] ?? '',
                    __('messages.stock.types.'.$m->movement_type), $m->documentLink()[0], $m->batch->batch_no ?? '', (int) $m->quantity,
                    round($m->quantity * $m->unit_cost, 2), $m->user->full_name ?? ''])->all();
                $head = [$t('date'), $t('store'), $t('item'), $t('type'), $t('document'), $t('batch'), $t('quantity'), $t('value'), $t('user')];
                break;

            case 'expiry':
                $batches = StockBatch::where('stockable_type', $class)->whereIn('store_id', $storeIds)->where('quantity', '>', 0)
                    ->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays($days)->toDateString())->orderBy('expiry_date')->get();
                $n = $names($batches->pluck('stockable_id')->unique()->all());
                $today = now()->startOfDay();
                $rows = $batches->map(fn ($b) => [$n[$b->stockable_id] ?? '', $storeNames[$b->store_id] ?? '', $b->batch_no ?? '',
                    Carbon::parse($b->expiry_date)->format('d/m/Y'), (int) $today->diffInDays(Carbon::parse($b->expiry_date), false),
                    (int) $b->quantity, round($b->quantity * $b->unit_cost, 2)])->all();
                $head = [$t('item'), $t('store'), $t('batch'), $t('expiry'), $t('days_left'), $t('quantity'), $t('value')];
                break;

            case 'valuation':
                $key = $group === 'item' ? 'stockable_id' : 'store_id';
                $data = StockBatch::where('stockable_type', $class)->whereIn('store_id', $storeIds)
                    ->selectRaw($key.' as id, SUM(quantity) as qty, SUM(quantity * unit_cost) as value')->groupBy($key)->get();
                $labels = $group === 'item' ? $names($data->pluck('id')->all()) : $storeNames;
                $rows = $data->map(fn ($r) => [$labels[$r->id] ?? '#'.$r->id, (int) $r->qty, round((float) $r->value, 2)])
                    ->sortByDesc(fn ($row) => $row[2])->values()->all();
                $head = [$group === 'item' ? $t('item') : $t('store'), $t('quantity'), $t('value')];
                break;

            default: // slow: in stock, nothing taken out for $days days
                $stock = StockBatch::where('stockable_type', $class)->whereIn('store_id', $storeIds)
                    ->selectRaw('store_id, stockable_id, SUM(quantity) as qty, SUM(quantity * unit_cost) as value')
                    ->groupBy('store_id', 'stockable_id')->havingRaw('SUM(quantity) > 0')->get();
                $lastOut = StockMovement::where('stockable_type', $class)->whereIn('store_id', $storeIds)->where('quantity', '<', 0)
                    ->selectRaw('store_id, stockable_id, MAX(created_at) as last_out')->groupBy('store_id', 'stockable_id')->get()
                    ->mapWithKeys(fn ($r) => [$r->store_id.':'.$r->stockable_id => $r->last_out]);
                $limit = now()->subDays($days);
                $n = $names($stock->pluck('stockable_id')->unique()->all());
                $rows = $stock->filter(fn ($r) => ! isset($lastOut[$r->store_id.':'.$r->stockable_id]) || Carbon::parse($lastOut[$r->store_id.':'.$r->stockable_id])->lt($limit))
                    ->map(fn ($r) => [$n[$r->stockable_id] ?? '', $storeNames[$r->store_id] ?? '',
                        isset($lastOut[$r->store_id.':'.$r->stockable_id]) ? Carbon::parse($lastOut[$r->store_id.':'.$r->stockable_id])->format('d/m/Y') : $t('never'),
                        (int) $r->qty, round((float) $r->value, 2)])
                    ->sortByDesc(fn ($row) => $row[4])->values()->all();
                $head = [$t('item'), $t('store'), $t('last_out'), $t('quantity'), $t('value')];
        }

        $valueColumn = array_search($t('value'), $head, true);
        $total = $rows ? array_fill(0, count($head), '') : null;
        if ($total) {
            $total[0] = $t('total');
            $total[$valueColumn] = round(array_sum(array_column($rows, $valueColumn)), 2);
        }

        return compact('report', 'filters', 'head', 'rows', 'total');
    }
}
