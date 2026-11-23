<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\FundTransfer;
use App\Models\MasterListItem;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use App\Models\Setting;
use App\Repositories\CashierRepository;
use Auth;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The cashier screen ("Caisse / Encaissement"): open the session, collect what patients
 * owe, print the receipt, and close the session with a closing number ("Arrêt de caisse").
 */
class CashierController extends AppBaseController
{
    /** @var CashierRepository */
    private $cashierRepository;

    public function __construct(CashierRepository $cashierRepo)
    {
        $this->cashierRepository = $cashierRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $session = $this->cashierRepository->openSessionFor($user);
        $patientId = $request->integer('patient') ?: null;
        $data = [
            'user' => $user,
            'session' => $session,
            'patients' => $session ? $this->cashierRepository->patientsToCollect() : [],
            'patientId' => $patientId,
            'items' => $session && $patientId ? $this->cashierRepository->unpaidItems($patientId) : collect(),
            'paymentMethods' => PaymentMethod::options(),
            'totals' => $session ? $session->netByMethod() : [],
            'transferTargets' => $session ? $this->transferTargets($session) : [],
            'recentReceipts' => $session ? $session->receipts()->with('patient.patientUser')->latest('id')->take(10)->get() : collect(),
        ];

        return view('cashier.index', $data);
    }

    /** Other registers with an open session, then the admin's destinations (safe, bank...). */
    private function transferTargets(CashSession $session): array
    {
        $targets = [];
        foreach (CashSession::with('counter')->whereNull('closed_at')->where('cash_counter_id', '!=', $session->cash_counter_id)->get() as $open) {
            $targets['counter:'.$open->cash_counter_id] = $open->counter->code.' · '.$open->counter->name;
        }
        foreach (MasterListItem::options(FundTransfer::DESTINATION_LIST) as $code => $label) {
            $targets['destination:'.$code] = $label;
        }

        return $targets;
    }

    public function openSession(Request $request)
    {
        $input = $request->validate(['opening_float' => 'nullable|numeric|min:0']);
        $this->cashierRepository->open(Auth::user(), (float) ($input['opening_float'] ?? 0));
        Flash::success(__('messages.cashier.session_opened'));

        return redirect(route('cashier.index', array_filter(['patient' => $request->input('patient')])));
    }

    public function collect(Request $request)
    {
        $session = $this->cashierRepository->openSessionFor(Auth::user());
        if (! $session) {
            Flash::error(__('messages.cashier.open_first'));

            return redirect(route('cashier.index'));
        }
        $input = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'items' => 'required|array|min:1',
            'items.*' => ['string', 'regex:/^[a-z_]+:\d+$/'],
            'payment_method' => ['required', 'integer', Rule::in(array_keys(PaymentMethod::options()))],
            'amount_received' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|max:50',
            'cheque_drawer' => 'nullable|string|max:100',
        ], [], ['items' => __('messages.cashier.items')]);

        $receipt = $this->cashierRepository->collect($session, Auth::user(), $input['patient_id'], $input['items'], $input);
        Flash::success(__('messages.cashier.collected', ['number' => $receipt->receipt_number, 'change' => formatMoney($receipt->change_given)]));

        return redirect(route('cashier.index', ['print' => $receipt->id]));
    }

    public function transfer(Request $request)
    {
        $session = $this->cashierRepository->openSessionFor(Auth::user());
        if (! $session) {
            Flash::error(__('messages.cashier.open_first'));

            return redirect(route('cashier.index'));
        }
        $input = $request->validate([
            'target' => ['required', 'string', 'regex:/^(counter|destination):\d+$/'],
            'payment_method' => ['required', 'integer', Rule::in(array_keys(PaymentMethod::options()))],
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
        ], [], ['target' => __('messages.fund_transfer.to')]);
        [$kind, $id] = explode(':', $input['target']);
        if ($kind === 'destination' && ! array_key_exists((int) $id, MasterListItem::options(FundTransfer::DESTINATION_LIST))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['target' => __('messages.fund_transfer.to')]);
        }
        $transfer = $this->cashierRepository->transfer($session, Auth::user(), $input + [
            'to_counter_id' => $kind === 'counter' ? (int) $id : null,
            'destination' => $kind === 'destination' ? (int) $id : null,
        ]);
        Flash::success(__('messages.fund_transfer.done', ['amount' => formatMoney($transfer->amount), 'to' => $transfer->destination_name]));

        return redirect(route('cashier.index'));
    }

    public function closeSession(Request $request)
    {
        $session = $this->cashierRepository->openSessionFor(Auth::user());
        if (! $session) {
            return redirect(route('cashier.index'));
        }
        $cash = CashierRepository::cashMethod();
        $input = $request->validate(['counted' => 'required|array', 'counted.'.$cash => 'required|numeric|min:0', 'counted.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'], [], ['counted.'.$cash => __('messages.cashier.counted_cash')]);
        $session = $this->cashierRepository->close($session, $input['counted'], $input['notes'] ?? null);
        Flash::success(__('messages.cashier.session_closed', ['number' => $session->closing_number])
            .($session->approval_status === 'pending' ? ' '.__('messages.cashier.close_pending') : ''));

        return redirect(route('cashier.index', ['closed' => $session->id]));
    }

    public function printReceipt(Receipt $receipt)
    {
        $receipt->load('lines', 'patient.patientUser', 'user', 'counter', 'refund');
        $settings = getSettingValue();
        $followupDays = (int) Setting::where('key', 'receipt_followup_days')->value('value');
        $paper = Setting::where('key', 'receipt_paper')->value('value') ?: 'a5';
        $footerText = trim((string) Setting::where('key', 'receipt_footer_text')->value('value'));
        $doctor = $receipt->lines->map(fn ($line) => $line->payable?->doctor?->doctorUser?->full_name)->filter()->first();

        return view('cashier.receipt', compact('receipt', 'settings', 'followupDays', 'paper', 'doctor', 'footerText'));
    }

    public function printSession(CashSession $cashSession)
    {
        $cashSession->load('counter', 'user', 'receipts.patient.patientUser', 'refunds.receipt.patient.patientUser', 'transfersOut.toCounter', 'transfersIn.fromCounter');
        $settings = getSettingValue();

        return view('cashier.session_report', ['session' => $cashSession, 'settings' => $settings,
            'totals' => $cashSession->netByMethod(), 'cashMethod' => CashierRepository::cashMethod()]);
    }
}
