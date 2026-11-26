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
            __('messages.exam_orders.packages') => \App\Models\Package::orderBy('name')->get()
                ->mapWithKeys(fn ($p) => ['package:'.$p->id => $p->name.' · '.formatMoney($p->total_amount)])->all(),
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
        \App\Utils\PriceSplit::assertPolicyAllowed($lines[0]['split']);

        $order = DB::transaction(fn () => ExamOrder::place($input, $lines, Auth::id()));

        Flash::success(__('messages.exam_orders.saved', ['amount' => formatMoney($order->items()->sum('patient_amount'))]));
        if (canAccessRoute('cashier.index')) {
            return redirect(route('cashier.index', ['patient' => $order->patient_id]));
        }

        return redirect(afterSaveUrl(route('exam-orders.index'), ['patient' => $order->patient_id]));
    }
}
