<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\ExamOrder;
use App\Models\Patient;
use App\Models\PathologyTest;
use App\Models\PriceList;
use App\Models\RadiologyTest;
use Auth;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Exam requests at reception ("Demande d'examens"): lab and radiology tests priced from the
 * price list, split between payer and patient, then paid at the cashier.
 */
class ExamOrderController extends AppBaseController
{
    const PER_PAGE = 25;

    public function index()
    {
        $orders = ExamOrder::with('patient.patientUser', 'doctor.doctorUser', 'items')->latest('id')->paginate(self::PER_PAGE);

        return view('exam_orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $tests = [
            __('messages.exam_orders.laboratory') => PathologyTest::orderBy('test_name')->get()
                ->mapWithKeys(fn ($t) => ['pathology_test:'.$t->id => $t->test_name.' · '.formatMoney($t->standard_charge)])->all(),
            __('messages.exam_orders.imaging') => RadiologyTest::orderBy('test_name')->get()
                ->mapWithKeys(fn ($t) => ['radiology_test:'.$t->id => $t->test_name.' · '.formatMoney($t->standard_charge)])->all(),
        ];
        $patients = Patient::with('patientUser')->get()->where('patientUser.status', 1)
            ->mapWithKeys(fn ($p) => [$p->id => $p->patientUser->full_name.' · '.$p->patient_unique_id])->sort()->all();
        $doctors = Doctor::with('doctorUser')->get()->where('doctorUser.status', 1)->pluck('doctorUser.full_name', 'id')->sort()->all();

        return view('exam_orders.create', [
            'tests' => $tests,
            'patients' => $patients,
            'doctors' => $doctors,
            'priceLists' => PriceList::options(),
            'defaultList' => PriceList::defaultList()?->id,
            'patientId' => $request->integer('patient_id') ?: null,
        ]);
    }

    public function getQuote(Request $request)
    {
        $lines = ExamOrder::priceTests((array) $request->input('tests', []), $request->all());

        return $this->sendResponse(['html' => view('exam_orders.quote', compact('lines'))->render()], '');
    }

    public function store(Request $request)
    {
        $input = $request->validate(ExamOrder::rules(), [], ['tests' => __('messages.exam_orders.exams')]);
        $lines = ExamOrder::priceTests($input['tests'], $input);
        if (! $lines) {
            throw ValidationException::withMessages(['tests' => __('messages.exam_orders.no_valid_test')]);
        }

        $order = DB::transaction(function () use ($input, $lines) {
            $order = ExamOrder::create([
                'patient_id' => $input['patient_id'],
                'doctor_id' => $input['doctor_id'] ?? null,
                // only a policy of this patient counts
                'patient_insurance_id' => \App\Models\PatientInsurance::where('patient_id', $input['patient_id'])->find($input['patient_insurance_id'] ?? null)?->id,
                'price_list_id' => $input['price_list_id'] ?? null,
                'voucher_number' => $input['voucher_number'] ?? null,
                'ordered_at' => now(),
                'user_id' => Auth::id(),
                'notes' => $input['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                $order->items()->create([
                    'testable_type' => $line['type'],
                    'testable_id' => $line['id'],
                    'description' => $line['description'],
                    'total' => $line['split']['gross'],
                    'discount_amount' => $line['split']['discount_amount'],
                    'payer_amount' => $line['split']['payer_amount'],
                    'patient_amount' => $line['split']['patient_amount'],
                ]);
            }

            return $order;
        });

        Flash::success(__('messages.exam_orders.saved', ['amount' => formatMoney($order->items()->sum('patient_amount'))]));
        if (canAccessRoute('cashier.index')) {
            return redirect(route('cashier.index', ['patient' => $order->patient_id]));
        }

        return redirect(afterSaveUrl(route('exam-orders.index'), ['patient' => $order->patient_id]));
    }
}
