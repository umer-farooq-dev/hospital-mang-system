<?php

namespace App\Http\Controllers;

use App\Models\CashCounter;
use App\Models\MasterListItem;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use App\Models\Refund;
use App\Repositories\CashierRepository;
use Auth;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Receipts log: find a receipt, print a duplicate, void it, refund it or correct its method.
 */
class ReceiptController extends AppBaseController
{
    const PER_PAGE = 25;

    /** @var CashierRepository */
    private $cashierRepository;

    public function __construct(CashierRepository $cashierRepo)
    {
        $this->cashierRepository = $cashierRepo;
    }

    public function index(Request $request)
    {
        $number = $request->integer('number') ?: null;
        $receipts = Receipt::with('patient.patientUser', 'user', 'counter', 'session', 'refund')
            ->when($number, fn ($q) => $q->where('receipt_number', $number))
            ->when(! $number && $request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from')))
            ->when(! $number && $request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to')))
            ->when($request->integer('counter'), fn ($q, $c) => $q->where('cash_counter_id', $c))
            ->when($request->integer('method'), fn ($q, $m) => $q->where('payment_method', $m))
            ->latest('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('receipts.index', [
            'receipts' => $receipts,
            'counters' => CashCounter::orderBy('code')->pluck('code', 'id')->all(),
            'methods' => PaymentMethod::options(),
            'reasons' => MasterListItem::options(Refund::REASON_LIST),
        ]);
    }

    private function reason(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'integer', Rule::in(array_keys(MasterListItem::options(Refund::REASON_LIST)))],
            'notes' => 'nullable|string|max:1000',
        ]);
    }

    public function void(Request $request, Receipt $receipt)
    {
        $input = $this->reason($request);
        $this->cashierRepository->void($receipt, Auth::user(), (int) $input['reason']);
        Flash::success(__('messages.receipts.voided', ['number' => $receipt->receipt_number]));

        return redirect()->back();
    }

    public function refund(Request $request, Receipt $receipt)
    {
        $input = $this->reason($request);
        $this->cashierRepository->refund($receipt, Auth::user(), (int) $input['reason'], $input['notes'] ?? null);
        Flash::success(__('messages.receipts.refunded', ['number' => $receipt->receipt_number, 'amount' => formatMoney($receipt->amount)]));

        return redirect()->back();
    }

    public function changeMethod(Request $request, Receipt $receipt)
    {
        $input = $request->validate(['payment_method' => ['required', 'integer', Rule::in(array_keys(PaymentMethod::options()))]]);
        $this->cashierRepository->changeMethod($receipt, (int) $input['payment_method']);
        Flash::success(__('messages.receipts.method_changed', ['number' => $receipt->receipt_number]));

        return redirect()->back();
    }
}
