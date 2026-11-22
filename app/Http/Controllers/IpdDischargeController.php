<?php

namespace App\Http\Controllers;

use App\Models\IpdPatientDepartment;
use App\Models\MasterListItem;
use App\Repositories\IpdBillRepository;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

/** Discharge sheet of an admission: outcome, checklist and discharge summary. */
class IpdDischargeController extends AppBaseController
{
    public function show(IpdPatientDepartment $ipdPatientDepartment, IpdBillRepository $bills)
    {
        $bill = $bills->getBillList($ipdPatientDepartment);

        return view('ipd_discharge.show', [
            'ipd' => $ipdPatientDepartment->load('patient.patientUser', 'doctor.doctorUser'),
            'outcomes' => MasterListItem::options('discharge_outcome', $ipdPatientDepartment->discharge_outcome),
            'checks' => MasterListItem::options('discharge_check'),
            'summary' => old('discharge_summary', $ipdPatientDepartment->discharge_summary ?: $ipdPatientDepartment->dischargeSummaryDraft()),
            'balance' => $ipdPatientDepartment->bill ? (float) $ipdPatientDepartment->bill->net_payable_amount : (float) $bill['net_payable_amount'],
            'settings' => \App\Models\Setting::whereIn('key', ['discharge_block_unpaid', 'discharge_require_checklist'])->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request, IpdPatientDepartment $ipdPatientDepartment)
    {
        $data = $request->validate([
            'discharge_date' => 'nullable|date|after_or_equal:' . \Illuminate\Support\Carbon::parse($ipdPatientDepartment->admission_date)->toDateString(),
            'discharge_outcome' => 'nullable|integer',
            'discharge_checks' => 'nullable|array',
            'discharge_checks.*' => 'nullable|integer',
            'discharge_summary' => 'nullable|string|max:20000',
        ]);
        $ipdPatientDepartment->update($data + ['discharge_checks' => []]);
        Flash::success(__('messages.discharge.saved'));

        return redirect()->route('ipd.discharge.show', $ipdPatientDepartment->id);
    }

    public function print(IpdPatientDepartment $ipdPatientDepartment)
    {
        return view('ipd_discharge.print', [
            'ipd' => $ipdPatientDepartment->load('patient.patientUser', 'doctor.doctorUser'),
            'summary' => $ipdPatientDepartment->discharge_summary ?: $ipdPatientDepartment->dischargeSummaryDraft(),
            'settings' => getSettingValue(),
        ]);
    }
}
