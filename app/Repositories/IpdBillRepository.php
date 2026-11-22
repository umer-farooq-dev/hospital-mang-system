<?php

namespace App\Repositories;

use App\Models\BedAssign;
use App\Models\Doctor;
use App\Models\IpdBill;
use App\Models\IpdCharge;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPayment;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Receptionist;
use App\Models\Setting;
use App\Models\User;

class IpdBillRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'total_payments',
        'gross_total',
        'discount_in_percentage',
        'tax_in_percentage',
        'other_charges',
        'net_payable_amount',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return IpdBill::class;
    }

    /** Advances on this admission's bill: those it already took, plus the patient's unused ones (often paid before admission). */
    public static function advancesFor(IpdPatientDepartment $ipd)
    {
        return \App\Models\AdvancedPayment::where('patient_id', $ipd->patient_id)
            ->where(fn ($q) => $q->where('ipd_patient_department_id', $ipd->id)->orWhereNull('ipd_patient_department_id'));
    }

    /** Package (priced from the admission's list) and the patient's pharmacy bills during the stay. */
    public function extraLines(IpdPatientDepartment $ipd, $list)
    {
        $lines = collect();
        $admitted = \Illuminate\Support\Carbon::parse($ipd->admission_date);
        if ($ipd->package) {
            $lines->push(['type' => __('messages.ipd_patient.package'), 'description' => $ipd->package->name, 'date' => $admitted,
                'amount' => $list ? $list->priceFor($ipd->package, $admitted) : (float) $ipd->package->total_amount]);
        }
        foreach (self::medicineBillsFor($ipd)->orderBy('bill_date')->get() as $medicineBill) {
            $lines->push(['type' => __('messages.ipd_bill_extra.pharmacy'), 'description' => $medicineBill->bill_number,
                'date' => \Illuminate\Support\Carbon::parse($medicineBill->bill_date), 'amount' => (float) $medicineBill->net_amount]);
        }

        return $lines;
    }

    /** Pharmacy bills on this admission: those it already took, plus the patient's unpaid ones since admission. */
    public static function medicineBillsFor(IpdPatientDepartment $ipd)
    {
        return \App\Models\MedicineBill::where('patient_id', $ipd->patient_id)
            ->where(fn ($q) => $q->where('ipd_patient_department_id', $ipd->id)
                ->orWhere(fn ($q) => $q->whereNull('ipd_patient_department_id')->where('payment_status', \App\Models\MedicineBill::UNPAID)
                    ->whereDate('bill_date', '>=', \Illuminate\Support\Carbon::parse($ipd->admission_date)->toDateString())));
    }

    /** Saving the bill ties those pharmacy bills to this admission so the pharmacy does not collect them again. */
    public static function claimMedicineBills(int $ipdId): int
    {
        $ipd = IpdPatientDepartment::find($ipdId);
        if (! $ipd) {
            return 0;
        }
        $ids = self::medicineBillsFor($ipd)->whereNull('ipd_patient_department_id')->pluck('id');

        return \App\Models\MedicineBill::whereIn('id', $ids)->update(['ipd_patient_department_id' => $ipd->id]);
    }

    /** Saving the bill ties the patient's unused advances to this admission so no later bill counts them again. */
    public static function claimAdvances(int $ipdId): int
    {
        $ipd = IpdPatientDepartment::find($ipdId);

        return $ipd ? \App\Models\AdvancedPayment::where('patient_id', $ipd->patient_id)
            ->whereNull('ipd_patient_department_id')->update(['ipd_patient_department_id' => $ipd->id]) : 0;
    }

    public function saveBill($input)
    {
        if (isset($input['bill_id'])) {
            $bill = $this->update($input, $input['bill_id']);
            self::claimAdvances($bill->ipd_patient_department_id);
        self::claimMedicineBills($bill->ipd_patient_department_id);

            return $bill;
        }
        $input['net_payable_amount'] = $input['net_payable_amount'];
        $input['other_charges'] = $input['other_charges'];
        $bill = $this->create($input);
        self::claimAdvances($bill->ipd_patient_department_id);
        self::claimMedicineBills($bill->ipd_patient_department_id);
        $ipdPatientDepartment = IpdPatientDepartment::with('bedAssign')->find($bill->ipd_patient_department_id);
        $patient = Patient::with('user')->where('id', $ipdPatientDepartment->patient_id)->first();
        $ipdPatientDepartment->bill_status = 1;
        $doctor = Doctor::with('user')->where('id', $ipdPatientDepartment->doctor_id)->first();
        $receptionists = Receptionist::pluck('user_id', 'id')->toArray();
        $userIds = [
            $doctor->user_id => Notification::NOTIFICATION_FOR[Notification::DOCTOR],
            $patient->user_id => Notification::NOTIFICATION_FOR[Notification::PATIENT],
        ];

        foreach ($receptionists as $key => $userId) {
            $userIds[$userId] = Notification::NOTIFICATION_FOR[Notification::RECEPTIONIST];
        }
        $adminUser = User::role('Admin')->first();
        $allUsers = $userIds + [$adminUser->id => Notification::NOTIFICATION_FOR[Notification::ADMIN]];
        $users = getAllNotificationUser($allUsers);

        foreach ($users as $key => $notification) {
            addNotification([
                Notification::NOTIFICATION_TYPE['IPD Patient'],
                $key,
                $notification,
                $patient->patientUser->full_name.', has been discharge.',
            ]);
        }
        $ipdPatientDepartment->save();

        $bedAssign = BedAssign::where('ipd_patient_department_id',$bill->ipd_patient_department_id)->first();

        if($bedAssign){
            $bedAssign->update(['status' => 0]);
        }
        // dd($bedAssign);

        $ipdPatientDepartment->bed->update(['is_available' => 1]);
        $ipdPatientDepartment->update(['discharge' => 1]);

        if ($ipdPatientDepartment->bedAssign) {
            BedAssign::where('id', $ipdPatientDepartment->bedAssign->id)->delete();
        }

        return $bill;
    }

    const BED_BASES = ['day', 'night', 'hour'];

    /** Units of bed charge between two moments: days (24 h started), nights (midnights) or hours; at least 1. */
    public static function bedUnits(\Illuminate\Support\Carbon $from, \Illuminate\Support\Carbon $to, string $basis): int
    {
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        return max(1, match ($basis) {
            'hour' => (int) ceil($from->diffInMinutes($to) / 60),
            'night' => (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()),
            default => (int) ceil($from->diffInMinutes($to) / 1440),
        });
    }

    /** Patient's net payable: everything charged, minus discounts, the payer's cover, payments and advances. */
    public static function netPayable(array $t): float
    {
        $base = $t['charges'] + $t['bed'];
        $discount = $t['discount_percent'] / 100 * $base;
        $tax = $t['tax_percent'] / 100 * $base; // tax on the charges only, never on payments

        return round($base + $t['other'] + $tax - $discount - $t['cover'] - $t['payments'] - $t['advances'], 2);
    }

    /** Bed segments of the stay (one per bed after transfers), each priced from the admission's price list. */
    public function bedLines($ipd, $list): \Illuminate\Support\Collection
    {
        $basis = \App\Models\Setting::where('key', 'bed_charge_basis')->value('value');
        $basis = in_array($basis, self::BED_BASES) ? $basis : 'day';
        $end = $ipd->bill?->created_at ?? now();
        $assigns = BedAssign::with('bed')->where('ipd_patient_department_id', $ipd->id)->orderBy('assign_date')->get();
        $segments = $assigns->isNotEmpty()
            ? $assigns->values()->map(fn ($a, $i) => [$a->bed, \Illuminate\Support\Carbon::parse($a->assign_date),
                $a->discharge_date ? \Illuminate\Support\Carbon::parse($a->discharge_date) : (isset($assigns[$i + 1]) ? \Illuminate\Support\Carbon::parse($assigns[$i + 1]->assign_date) : $end)])
            : collect([[$ipd->bed, \Illuminate\Support\Carbon::parse($ipd->admission_date), $end]]);

        return $segments->filter(fn ($s) => $s[0])->map(function ($s) use ($basis, $list) {
            [$bed, $from, $to] = $s;
            $units = self::bedUnits($from, $to, $basis);
            $unitPrice = $list ? $list->priceFor($bed, $from) : (float) $bed->charge;

            return ['bed' => $bed, 'from' => $from, 'to' => $to, 'units' => $units, 'basis' => $basis, 'unit_price' => $unitPrice, 'total' => round($units * $unitPrice, 2)];
        })->values();
    }

    public function getBillList($ipdPatientDepartment)
    {
        $ipd = IpdPatientDepartment::with('bed', 'doctor', 'patientInsurance.insurance.priceList', 'priceList', 'bill', 'package')->find($ipdPatientDepartment->id);
        $policy = $ipd->patientInsurance;
        $list = $ipd->priceList ?? $policy?->insurance?->priceList ?? \App\Models\PriceList::defaultList();
        $bill['bed_lines'] = $this->bedLines($ipd, $list);
        $bedCharges = $bill['bed_lines']->sum('total');
        $bill['bed_charges'] = $bedCharges;
        $bill['charges'] = IpdCharge::with(['chargecategory'])
            ->whereIpdPatientDepartmentId($ipdPatientDepartment->id)->get();
        $bill['extra_lines'] = $this->extraLines($ipd, $list);
        $bill['exam_items'] = \App\Models\ExamOrderItem::with('order')->whereHas('order', fn ($q) => $q->where('ipd_patient_department_id', $ipd->id))->get();
        $bill['advances'] = self::advancesFor($ipd)->get();
        $bill['price_list'] = $list;
        $bill['policy'] = $policy;

        // Payer's cover (reductions + insurer share) line by line, as on the OPD visits.
        $departmentId = $ipd->doctor?->doctor_department_id;
        $options = ['voucher_number' => $ipd->voucher_number];
        $cover = 0;
        $split = fn ($amount, $category, $date) => \App\Utils\PriceSplit::compute((float) $amount, $policy, $departmentId, $options + ['charge_category_id' => $category, 'date' => $date]);
        foreach ($bill['charges'] as $charge) {
            $s = $split($charge->applied_charge, $charge->charge_category_id, $charge->date);
            $cover += $s['discount_amount'] + $s['payer_amount'];
        }
        foreach ($bill['exam_items'] as $item) {
            $cover += $item->discount_amount + $item->payer_amount; // split when the exam was requested
        }
        foreach ($bill['bed_lines'] as $line) {
            $s = $split($line['total'], null, $line['from']);
            $cover += $s['discount_amount'] + $s['payer_amount'];
        }
        foreach ($bill['extra_lines'] as $line) {
            $s = $split($line['amount'], null, $line['date']);
            $cover += $s['discount_amount'] + $s['payer_amount'];
        }
        $bill['cover_amount'] = round($cover, 2);
        $bill['advances_total'] = (float) $bill['advances']->sum('amount');
        $bill['payments'] = IpdPayment::whereIpdPatientDepartmentId($ipdPatientDepartment->id)->get();

        $bill['ipd_patient_department'] = $ipdPatientDepartment;
        $ipdPatientBill = $ipdPatientDepartment->bill;

        if ($ipdPatientBill) {
            $bill['last_net_payable_amount'] = ($ipdPatientBill->net_payable_amount > 0) ? $ipdPatientBill->net_payable_amount : 0;
        }

        //calculate bill
        $discountInPercentage = (! is_null($ipdPatientBill)) ? $ipdPatientBill->discount_in_percentage : 0;
        $taxInPercentage = (! is_null($ipdPatientBill)) ? $ipdPatientBill->tax_in_percentage : 0;
        $otherChareges = (! is_null($ipdPatientBill)) ? $ipdPatientBill->other_charges : 0;

        $totalCharges = $bill['charges']->sum('applied_charge') + $bill['exam_items']->sum('total') + $bill['extra_lines']->sum('amount');
        $totalPayment = $bill['payments']->sum('amount');

        $netPayableAmount = self::netPayable([
            'charges' => $totalCharges, 'bed' => $bedCharges, 'other' => (float) $otherChareges,
            'discount_percent' => (float) $discountInPercentage, 'tax_percent' => (float) $taxInPercentage,
            'cover' => $bill['cover_amount'], 'payments' => $totalPayment, 'advances' => $bill['advances_total'],
        ]);

        //patient side payment amount
        $bill['patient_net_payable_amount'] = ($ipdPatientBill) ? $ipdPatientBill->net_payable_amount : $netPayableAmount;

        $grossTotal = $totalCharges + $bedCharges;
        $bill['total_charges'] = $totalCharges;
        $bill['total_payment'] = $totalPayment;
        $bill['gross_total'] = $grossTotal;
        $bill['discount_in_percentage'] = $discountInPercentage;
        $bill['tax_in_percentage'] = $taxInPercentage;
        $bill['other_charges'] = $otherChareges;
        $bill['net_payable_amount'] = $netPayableAmount;

        return $bill;
    }

    public function getSyncListForCreate()
    {
        $data['setting'] = Setting::pluck('value', 'key');

        return $data;
    }
}
