<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\DoctorOPDCharge;
use App\Models\ExamOrder;
use App\Models\OpdPatientDepartment;
use App\Models\PathologyTest;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\PaymentMethod;
use App\Models\PriceList;
use App\Models\RadiologyTest;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceptionDeskRepository
{
    const SEARCH_LIMIT = 15;

    const RECENT_LIMIT = 8;

    public function search(string $term): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        // The hospital ID card QR holds a link ending with the patient number.
        if (filter_var($term, FILTER_VALIDATE_URL)) {
            $term = basename(parse_url($term, PHP_URL_PATH) ?: $term);
        }

        $idNumber = Patient::normalizeIdNumber($term);
        $digits = preg_replace('/\D/', '', $term);
        $words = preg_split('/\s+/', $term);

        return Patient::with(['patientUser.media', 'insurances.insurance'])
            ->where(function (Builder $q) use ($term, $idNumber, $digits, $words) {
                $q->where('patient_unique_id', $term)
                    ->orWhere('id_document_number', $idNumber)
                    ->orWhereHas('patientUser', function (Builder $u) use ($words, $digits) {
                        $u->where(function (Builder $name) use ($words) {
                            // Every word must appear in the first or last name, in any order.
                            foreach ($words as $word) {
                                $name->where(fn ($w) => $w->where('first_name', 'like', '%'.$word.'%')
                                    ->orWhere('last_name', 'like', '%'.$word.'%'));
                            }
                        });
                        if (strlen($digits) >= 4) {
                            $u->orWhere('phone', 'like', '%'.$digits.'%');
                        }
                    });
            })
            ->latest('id')
            ->limit(self::SEARCH_LIMIT)
            ->get();
    }

    public function todayAppointments(): Collection
    {
        return Appointment::with(['patient.patientUser', 'doctor.doctorUser', 'doctor.department'])
            ->whereDate('opd_date', today())
            ->whereHas('patient')
            ->orderBy('opd_date')
            ->get();
    }

    public function recentPatients(): Collection
    {
        return Patient::with(['patientUser.media', 'insurances.insurance'])->whereHas('patientUser')->latest('id')->limit(self::RECENT_LIMIT)->get();
    }

    /** Highest reduction (%) a desk user may grant on one visit (Settings > Reception; 0 = none). */
    public static function maxDiscount(): float
    {
        return max(0, min(100, (float) (Setting::where('key', 'reception_max_discount')->value('value') ?? 0)));
    }

    /** Everything the one-page visit form offers for this patient. */
    public function visitForm(Patient $patient): array
    {
        $doctors = Doctor::with('doctorUser', 'department')->whereHas('doctorUser', fn (Builder $q) => $q->where('status', 1))->get()
            ->sortBy('doctorUser.full_name');
        $policies = $patient->insurances()->with('insurance')->get()->filter(fn (PatientInsurance $p) => $p->is_active && $p->isValidOn());
        $tests = [
            __('messages.exam_orders.laboratory') => PathologyTest::orderBy('test_name')->pluck('test_name', 'id')->mapWithKeys(fn ($n, $id) => ['pathology_test:'.$id => $n])->all(),
            __('messages.exam_orders.imaging') => RadiologyTest::orderBy('test_name')->pluck('test_name', 'id')->mapWithKeys(fn ($n, $id) => ['radiology_test:'.$id => $n])->all(),
            __('messages.exam_orders.packages') => \App\Models\Package::orderBy('name')->pluck('name', 'id')->mapWithKeys(fn ($n, $id) => ['package:'.$id => $n])->all(),
        ];

        return [
            'departments' => DoctorDepartment::orderBy('title')->pluck('title', 'id')->all(),
            // doctor id => [name, department id] for the department filter
            'doctors' => $doctors->mapWithKeys(fn ($d) => [$d->id => ['name' => $d->doctorUser->full_name, 'department' => $d->doctor_department_id, 'specialist' => $d->specialist]])->all(),
            'tests' => array_filter($tests),
            'policies' => $policies->mapWithKeys(fn ($p) => [$p->id => $p->option_label])->all(),
            'defaultPolicy' => PatientInsurance::defaultFor($policies)?->id,
            'priceLists' => PriceList::options(),
            'defaultList' => PriceList::defaultList()?->id,
            'paymentMethods' => PaymentMethod::options(),
            'cashMethod' => CashierRepository::cashMethod(),
            'maxDiscount' => self::maxDiscount(),
        ];
    }

    /** Fee of the doctor's consultation in the chosen price list. */
    private function consultationFee(int $doctorId, ?int $priceListId): float
    {
        $charge = DoctorOPDCharge::where('doctor_id', $doctorId)->first();
        $list = PriceList::find($priceListId) ?? PriceList::defaultList();

        return $charge ? (float) ($list ? $list->priceFor($charge, now()) : $charge->standard_charge) : 0.0;
    }

    /**
     * Priced lines of the visit (consultation first, then the exams), split between payer and patient.
     *
     * @return array<int, array{type: string, description: string, split: array}>
     */
    public function quote(Patient $patient, array $input): array
    {
        $input['patient_id'] = $patient->id;
        $lines = [];
        if (! empty($input['consultation']) && ! empty($input['doctor_id'])) {
            $visitInput = $input + ['standard_charge' => $this->consultationFee((int) $input['doctor_id'], $input['price_list_id'] ?? null), 'appointment_date' => now()];
            [, $split] = OpdPatientDepartment::splitFor($visitInput);
            $doctor = Doctor::with('doctorUser')->find($input['doctor_id']);
            $lines[] = ['type' => 'consultation', 'description' => __('messages.cashier.consultation').' · '.($doctor?->doctorUser->full_name ?? ''), 'split' => $split];
        }
        foreach (ExamOrder::priceTests((array) ($input['tests'] ?? []), $input) as $line) {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Save the visit (consultation and/or exams) and, when the desk user has an open cash session,
     * take the patient's share on one receipt. Without a session the items wait at the cashier.
     *
     * @return array{visit: ?OpdPatientDepartment, order: ?ExamOrder, receipt: ?Receipt}
     */
    public function createVisit(Patient $patient, array $input, User $user, CashierRepository $cashier): array
    {
        if (empty($input['consultation']) && empty($input['tests'])) {
            throw ValidationException::withMessages(['tests' => __('messages.reception_visit.nothing_chosen')]);
        }
        if (! empty($input['consultation']) && empty($input['doctor_id'])) {
            throw ValidationException::withMessages(['doctor_id' => __('messages.reception_visit.doctor_needed')]);
        }
        if ((float) ($input['extra_discount'] ?? 0) > self::maxDiscount()) {
            throw ValidationException::withMessages(['extra_discount' => __('messages.reception_visit.discount_too_high', ['max' => formatPercent(self::maxDiscount())])]);
        }
        $input['patient_id'] = $patient->id;
        $session = $cashier->openSessionFor($user);

        return DB::transaction(function () use ($patient, $input, $user, $cashier, $session) {
            $visit = null;
            $order = null;
            if (! empty($input['consultation'])) {
                $visit = OpdPatientDepartment::create(OpdPatientDepartment::withSplit([
                    'patient_id' => $patient->id,
                    'doctor_id' => (int) $input['doctor_id'],
                    'appointment_date' => now(),
                    'opd_number' => OpdPatientDepartment::generateUniqueOpdNumber(),
                    'standard_charge' => $this->consultationFee((int) $input['doctor_id'], $input['price_list_id'] ?? null),
                    'payment_mode' => (int) ($input['payment_method'] ?? CashierRepository::cashMethod()),
                    'patient_insurance_id' => $input['patient_insurance_id'] ?? null,
                    'price_list_id' => $input['price_list_id'] ?? null,
                    'voucher_number' => $input['voucher_number'] ?? null,
                    'extra_discount' => $input['extra_discount'] ?? null,
                    'is_old_patient' => OpdPatientDepartment::where('patient_id', $patient->id)->exists(),
                ]));
            }
            if (! empty($input['tests'])) {
                $lines = ExamOrder::priceTests($input['tests'], $input);
                if ($lines) {
                    \App\Utils\PriceSplit::assertPolicyAllowed($lines[0]['split']);
                    $order = ExamOrder::place($input, $lines, $user->id);
                }
            }

            $receipt = null;
            $keys = array_merge($visit ? ['opd_visit:'.$visit->id] : [],
                $order && ! $order->ipd_patient_department_id ? $order->items()->pluck('id')->map(fn ($id) => 'exam_item:'.$id)->all() : []);
            if ($session && $keys) {
                $due = $cashier->unpaidItems($patient->id)->whereIn('key', $keys)->pluck('key')->all();
                if ($due) {
                    $receipt = $cashier->collect($session, $user, $patient->id, $due, [
                        'payment_method' => (int) ($input['payment_method'] ?? CashierRepository::cashMethod()),
                        'amount_received' => $input['amount_received'] ?? null,
                        'cheque_number' => $input['cheque_number'] ?? null,
                    ]);
                }
            }

            return ['visit' => $visit, 'order' => $order, 'receipt' => $receipt];
        });
    }
}
