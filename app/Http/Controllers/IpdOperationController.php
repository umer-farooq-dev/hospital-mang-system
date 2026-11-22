<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateIPDOperationRequest;
use App\Models\IpdOperation;
use App\Models\MasterListItem;
use App\Models\OperationFollowup;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Str;

class IpdOperationController extends AppBaseController
{
    public function store(CreateIPDOperationRequest $request)
    {
        $operationRefNo = strtoupper(Str::random(8));

        while (true) {
            $isExist = IpdOperation::where('ref_no', $operationRefNo)->exists();
            if ($isExist) {
                $operationRefNo = strtoupper(Str::random(8));
            }
            break;
        }

        $input = [
            'ref_no' => $operationRefNo,
            'operation_category_id' => $request->operation_category_id,
            'operation_id' => $request->operation_id,
            'ipd_patient_department_id' => $request->ipd_patient_department_id,
            'operation_date' => $request->operation_date,
            'doctor_id' => $request->doctor_id,
            'assistant_consultant_1' => $request->assistant_consultant_1,
            'assistant_consultant_2' => $request->assistant_consultant_2,
            'anesthetist' => $request->anesthetist,
            'anesthesia_type' => $request->anesthesia_type,
            'ot_technician' => $request->ot_technician,
            'ot_assistant' => $request->ot_assistant,
            'remark' => $request->remark,
            'result' => $request->result,
        ] + $request->only(IpdOperation::OT_FIELDS);

        IpdOperation::create($input);

        return $this->sendSuccess(__('messages.ipd_operation.ipd_operation_created_successfully'));
    }

    public function edit($id)
    {
        $data = IpdOperation::where('id', $id)->first();

        return $this->sendResponse($data, 'IPD operation retrieved successfully.');
    }

    public function update($id, CreateIPDOperationRequest $request)
    {
        $ipdOperation = IpdOperation::where('id', $id)->first();
        $ipdOperation->update($request->all());

        return $this->sendSuccess(__('messages.ipd_operation.ipd_operation').' '.__('messages.common.updated_successfully'));
    }

    /** Operation sheet: details, team, pre-op checklist, K fees and post-op notes. */
    public function show($id)
    {
        return view('ipd_operation.sheet', $this->sheetData($id));
    }

    public function print($id)
    {
        return view('ipd_operation.print', $this->sheetData($id) + ['settings' => getSettingValue()]);
    }

    public function storeFollowup(Request $request, $id)
    {
        $operation = IpdOperation::findOrFail($id);
        $data = $request->validate(['noted_at' => 'required|date', 'note' => 'required|string|max:5000']);
        OperationFollowup::create($data + ['ipd_operation_id' => $operation->id, 'created_by' => auth()->id()]);
        Flash::success(__('messages.ot.followup_saved'));

        return redirect()->route('operation.sheet', $operation->id);
    }

    private function sheetData($id): array
    {
        $operation = IpdOperation::with(['ipd_patient_department.patient.patientUser', 'operations.operation_category', 'surgeon.doctorUser',
            'assistantDoctor.doctorUser', 'anaesthetistDoctor.doctorUser', 'followups.author'])->findOrFail($id);

        return [
            'operation' => $operation,
            'checklist' => MasterListItem::labels('preop_check'),
            'activeChecks' => MasterListItem::options('preop_check'),
        ];
    }

    public function delete($id)
    {
        IpdOperation::where('id', $id)->delete();

        return $this->sendSuccess(__('messages.ipd_operation.ipd_operation').' '.__('messages.common.deleted_successfully'));
    }
}
