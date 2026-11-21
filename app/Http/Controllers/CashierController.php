<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
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
            'visits' => $session && $patientId ? $this->cashierRepository->unpaidVisits($patientId) : collect(),
            'paymentMethods' => PaymentMethod::options(),
            'totals' => $session ? $session->totalsByMethod() : [],
            'recentReceipts' => $session ? $session->receipts()->with('patient.patientUser')->latest('id')->take(10)->get() : collect(),
        ];

        return view('cashier.index', $data);
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
            'visits' => 'required|array|min:1',
            'visits.*' => 'integer',
            'payment_method' => ['required', 'integer', Rule::in(array_keys(PaymentMethod::options()))],
            'amount_received' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|max:50',
            'cheque_drawer' => 'nullable|string|max:100',
        ], [], ['visits' => __('messages.cashier.items')]);

        $receipt = $this->cashierRepository->collect($session, Auth::user(), $input['patient_id'], $input['visits'], $input);
        Flash::success(__('messages.cashier.collected', ['number' => $receipt->receipt_number, 'change' => formatMoney($receipt->change_given)]));

        return redirect(route('cashier.index', ['print' => $receipt->id]));
    }

    public function closeSession(Request $request)
    {
        $session = $this->cashierRepository->openSessionFor(Auth::user());
        if (! $session) {
            return redirect(route('cashier.index'));
        }
        $input = $request->validate(['counted_cash' => 'required|numeric|min:0', 'notes' => 'nullable|string|max:1000']);
        $session = $this->cashierRepository->close($session, (float) $input['counted_cash'], $input['notes'] ?? null);
        Flash::success(__('messages.cashier.session_closed', ['number' => $session->closing_number]));

        return redirect(route('cashier.index', ['closed' => $session->id]));
    }

    public function printReceipt(Receipt $receipt)
    {
        $receipt->load('lines', 'patient.patientUser', 'user', 'counter');
        $settings = getSettingValue();
        $followupDays = (int) Setting::where('key', 'receipt_followup_days')->value('value');
        $paper = Setting::where('key', 'receipt_paper')->value('value') ?: 'a5';
        $doctor = optional($receipt->lines->first())->payable?->doctor?->doctorUser?->full_name;

        return view('cashier.receipt', compact('receipt', 'settings', 'followupDays', 'paper', 'doctor'));
    }

    public function printSession(CashSession $cashSession)
    {
        $cashSession->load('counter', 'user', 'receipts.patient.patientUser');
        $settings = getSettingValue();

        return view('cashier.session_report', ['session' => $cashSession, 'settings' => $settings,
            'totals' => $cashSession->totalsByMethod(), 'cashMethod' => CashierRepository::cashMethod()]);
    }
}
