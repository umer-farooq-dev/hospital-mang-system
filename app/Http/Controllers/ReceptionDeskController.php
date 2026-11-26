<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Repositories\ReceptionDeskRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

class ReceptionDeskController extends AppBaseController
{
    /** @var ReceptionDeskRepository */
    private $receptionDeskRepository;

    public function __construct(ReceptionDeskRepository $receptionDeskRepo)
    {
        $this->receptionDeskRepository = $receptionDeskRepo;
    }

    public function index(Request $request)
    {
        $appointments = $this->receptionDeskRepository->todayAppointments();
        $recentPatients = $this->receptionDeskRepository->recentPatients();
        // Coming back from "New patient": show that patient ready for the next step.
        $selected = $request->filled('patient')
            ? Patient::with(['patientUser.media', 'insurances.insurance'])->whereKey($request->integer('patient'))->get()
            : collect();

        $webcam = Patient::registrationSettings()['webcam'];

        return view('reception_desk.index', compact('appointments', 'recentPatients', 'selected', 'webcam'));
    }

    /**
     * Search by name, phone, patient number, ID number, or a scanned hospital card.
     */
    public function getSearch(Request $request)
    {
        $patients = $this->receptionDeskRepository->search((string) $request->get('search'));

        return $this->sendResponse(view('reception_desk.patient_results', compact('patients'))->render(), '');
    }

    /** One page: department, doctor, exams, payer and discount, then the receipt. */
    public function visit(Patient $patient)
    {
        $patient->load('patientUser', 'insurances.insurance');
        $form = $this->receptionDeskRepository->visitForm($patient);
        $session = app(\App\Repositories\CashierRepository::class)->openSessionFor(auth()->user());
        $hasCounter = (bool) auth()->user()->cash_counter_id;

        return view('reception_desk.visit', array_merge($form, compact('patient', 'session', 'hasCounter')));
    }

    /** Live price of the visit while the form is filled in. */
    public function visitQuote(Request $request, Patient $patient)
    {
        $lines = $this->receptionDeskRepository->quote($patient, $this->visitInput($request, false));
        $total = fn ($key) => round(array_sum(array_map(fn ($l) => $l['split'][$key], $lines)), 2);
        $warnings = array_values(array_unique(array_merge(...array_map(fn ($l) => $l['split']['warnings'] ?? [], $lines ?: [['split' => []]]))));

        return $this->sendResponse([
            'lines' => array_map(fn ($l) => [
                'description' => $l['description'],
                'gross' => formatMoney($l['split']['gross']),
                'discount' => formatMoney($l['split']['discount_amount']),
                'payer' => formatMoney($l['split']['payer_amount']),
                'patient' => formatMoney($l['split']['patient_amount']),
            ], $lines),
            'totals' => ['gross' => formatMoney($total('gross')), 'discount' => formatMoney($total('discount_amount')),
                'payer' => formatMoney($total('payer_amount')), 'patient' => formatMoney($total('patient_amount'))],
            'patient_due' => $total('patient_amount'),
            'warnings' => array_map(fn ($w) => __('messages.split.warnings.'.$w), $warnings),
        ], '');
    }

    public function storeVisit(Request $request, Patient $patient)
    {
        $result = $this->receptionDeskRepository->createVisit($patient, $this->visitInput($request, true), auth()->user(),
            app(\App\Repositories\CashierRepository::class));

        if ($result['receipt']) {
            Flash::success(__('messages.reception_visit.paid', ['number' => $result['receipt']->receipt_number,
                'change' => formatMoney($result['receipt']->change_given)]));

            return redirect(route('reception-desk.index', ['printed' => $result['receipt']->id]));
        }
        Flash::success(__('messages.reception_visit.saved_to_cashier'));

        return redirect(route('reception-desk.index', ['patient' => $patient->id]));
    }

    private function visitInput(Request $request, bool $saving): array
    {
        return $request->validate([
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'consultation' => 'nullable|boolean',
            'tests' => 'nullable|array',
            'tests.*' => ['string', 'regex:/^(pathology_test|radiology_test|package):\d+$/'],
            'patient_insurance_id' => 'nullable|integer|exists:patient_insurances,id',
            'price_list_id' => 'nullable|integer|exists:price_lists,id',
            'voucher_number' => 'nullable|string|max:50',
            'extra_discount' => 'nullable|numeric|min:0|max:100',
            'payment_method' => [$saving ? 'required' : 'nullable', 'integer', \Illuminate\Validation\Rule::in(array_keys(\App\Models\PaymentMethod::options()))],
            'amount_received' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|max:50',
        ]);
    }
}
