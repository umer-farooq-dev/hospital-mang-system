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
        $receipts = Receipt::with('patient.patientUser', 'user', 'counter', 'session', 'refund', 'lines')
            ->when($number, fn ($q) => $q->where('receipt_number', $number))
            ->when(! $number && $request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from')))
            ->when(! $number && $request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to')))
            ->when($request->integer('counter'), fn ($q, $c) => $q->where('cash_counter_id', $c))
            ->when($request->integer('method'), fn ($q, $m) => $q->where('payment_method', $m))
            ->latest('id')->paginate(request()->attributes->get('exporting') ? 100000 : self::PER_PAGE)->withQueryString();

        return view('receipts.index', [
            'receipts' => $receipts,
            'counters' => CashCounter::orderBy('code')->pluck('code', 'id')->all(),
            'methods' => PaymentMethod::options(),
            'reasons' => MasterListItem::options(Refund::REASON_LIST),
            'doctors' => \App\Models\Doctor::with('doctorUser')->get()->where('doctorUser.status', 1)->pluck('doctorUser.full_name', 'id')->sort()->all(),
            'pendingVoids' => Receipt::with('voidRequester', 'patient.patientUser')->whereNotNull('void_requested_at')->whereNull('voided_at')->latest('void_requested_at')->get(),
            'pendingCloses' => \App\Models\CashSession::with('user', 'counter')->where('approval_status', 'pending')->latest('closed_at')->get(),
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
        if (CashierRepository::voidNeedsApproval()) {
            $this->cashierRepository->requestVoid($receipt, Auth::user(), (int) $input['reason']);
            Flash::success(__('messages.receipts.void_requested', ['number' => $receipt->receipt_number]));
        } else {
            $this->cashierRepository->void($receipt, Auth::user(), (int) $input['reason']);
            Flash::success(__('messages.receipts.voided', ['number' => $receipt->receipt_number]));
        }

        return redirect()->back();
    }

    /** Second person confirms a requested void. */
    public function approveVoid(Receipt $receipt)
    {
        $this->cashierRepository->approveVoid($receipt, Auth::user());
        Flash::success(__('messages.receipts.voided', ['number' => $receipt->receipt_number]));

        return redirect()->back();
    }

    public function cancelVoidRequest(Receipt $receipt)
    {
        $this->cashierRepository->cancelVoidRequest($receipt);
        Flash::success(__('messages.receipts.void_request_cancelled', ['number' => $receipt->receipt_number]));

        return redirect()->back();
    }

    /** A supervisor approves a register close whose difference is above the allowed amount (or every close, per setting). */
    public function approveSession(Request $request, \App\Models\CashSession $cashSession)
    {
        $input = $request->validate(['approval_note' => 'nullable|string|max:1000']);
        $this->cashierRepository->approveClose($cashSession, Auth::user(), $input['approval_note'] ?? null);
        Flash::success(__('messages.cashier.close_approved', ['number' => $cashSession->closing_number]));

        return redirect()->back();
    }

    public function refund(Request $request, Receipt $receipt)
    {
        $input = $this->reason($request);
        $this->cashierRepository->refund($receipt, Auth::user(), (int) $input['reason'], $input['notes'] ?? null);
        Flash::success(__('messages.receipts.refunded', ['number' => $receipt->receipt_number, 'amount' => formatMoney($receipt->amount)]));

        return redirect()->back();
    }

    /** Credit the consultation(s) of a receipt to another doctor, as the old "Changer médecin". */
    public function changeDoctor(Request $request, Receipt $receipt)
    {
        $input = $request->validate(['doctor_id' => 'required|integer|exists:doctors,id']);
        $visitIds = $receipt->lines()->where('payable_type', 'opd_visit')->pluck('payable_id');
        if ($visitIds->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['doctor_id' => __('messages.receipts.no_consultation')]);
        }
        foreach (\App\Models\OpdPatientDepartment::whereIn('id', $visitIds)->get() as $visit) {
            $visit->update(['doctor_id' => (int) $input['doctor_id']]); // audited by the model listener
        }
        Flash::success(__('messages.receipts.doctor_changed', ['number' => $receipt->receipt_number]));

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
