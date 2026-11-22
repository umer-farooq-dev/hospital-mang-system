<?php

namespace App\Http\Controllers;

use App\Models\IntakeOutput;
use App\Models\IpdPatientDepartment;
use App\Models\MasterListItem;
use App\Models\MedicationAdministration;
use App\Models\NursingNote;
use App\Models\VitalReading;
use App\Models\VitalType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;

/** Nursing chart of an admission: vitals, notes per shift, intake and output, medication administration record. */
class NursingChartController extends AppBaseController
{
    public function show(IpdPatientDepartment $ipdPatientDepartment)
    {
        $ipd = $ipdPatientDepartment->load('patient.patientUser', 'bed');
        MedicationAdministration::generateFor($ipd);
        $readings = VitalReading::with('author')->where('ipd_patient_department_id', $ipd->id)->orderBy('measured_at')->get();
        $types = VitalType::active();
        $usedTypeIds = $readings->pluck('vital_type_id')->unique();
        $types = $types->merge(VitalType::whereIn('id', $usedTypeIds)->whereNotIn('id', $types->pluck('id'))->get());
        $flows = IntakeOutput::with('author')->where('ipd_patient_department_id', $ipd->id)->orderByDesc('recorded_at')->get();

        return view('ipd_nursing.show', [
            'ipd' => $ipd,
            'types' => $types,
            'rounds' => $readings->groupBy(fn ($r) => $r->measured_at->format('Y-m-d H:i'))->sortKeysDesc(),
            'chart' => $types->filter(fn ($t) => $usedTypeIds->contains($t->id))->map(fn ($t) => [
                'label' => $t->label . ($t->unit ? ' (' . $t->unit . ')' : ''),
                'points' => $readings->where('vital_type_id', $t->id)->map(fn ($r) => ['x' => $r->measured_at->format('Y-m-d H:i'), 'y' => $r->value])->values(),
            ])->values(),
            'notes' => NursingNote::with('author')->where('ipd_patient_department_id', $ipd->id)->orderByDesc('noted_at')->get(),
            'flows' => $flows,
            'balance' => IntakeOutput::dailyBalance($flows),
            'doses' => MedicationAdministration::with('item.medicine', 'author')->where('ipd_patient_department_id', $ipd->id)
                ->where('scheduled_at', '<=', now()->addDay()->endOfDay())->orderBy('scheduled_at')->get()
                ->groupBy(fn ($d) => $d->scheduled_at->format('Y-m-d'))->sortKeysDesc(),
            'shifts' => MasterListItem::options('nursing_shift'),
            'intakeTypes' => MasterListItem::options('intake_type'),
            'outputTypes' => MasterListItem::options('output_type'),
            'marStatuses' => MasterListItem::options('mar_status'),
        ]);
    }

    public function storeVitals(Request $request, IpdPatientDepartment $ipdPatientDepartment)
    {
        $data = $request->validate([
            'measured_at' => 'required|date',
            'values' => 'required|array',
            'values.*' => 'nullable|numeric|min:-1000|max:100000',
            'note' => 'nullable|string|max:500',
        ]);
        $typeIds = VitalType::pluck('id')->all();
        $saved = 0;
        foreach ($data['values'] as $typeId => $value) {
            if ($value === null || $value === '' || ! in_array((int) $typeId, $typeIds, true)) {
                continue;
            }
            VitalReading::create(['ipd_patient_department_id' => $ipdPatientDepartment->id, 'vital_type_id' => (int) $typeId, 'value' => $value,
                'measured_at' => $data['measured_at'], 'note' => $data['note'] ?? null, 'recorded_by' => auth()->id()]);
            $saved++;
        }
        $saved ? Flash::success(__('messages.nursing.vitals_saved')) : Flash::error(__('messages.nursing.no_value'));

        return redirect()->to(route('ipd.nursing.show', $ipdPatientDepartment->id) . '#vitals');
    }

    public function storeNote(Request $request, IpdPatientDepartment $ipdPatientDepartment)
    {
        $data = $request->validate(['shift' => 'nullable|integer', 'noted_at' => 'required|date', 'note' => 'required|string|max:5000']);
        NursingNote::create($data + ['ipd_patient_department_id' => $ipdPatientDepartment->id, 'recorded_by' => auth()->id()]);
        Flash::success(__('messages.nursing.note_saved'));

        return redirect()->to(route('ipd.nursing.show', $ipdPatientDepartment->id) . '#notes');
    }

    public function storeIntakeOutput(Request $request, IpdPatientDepartment $ipdPatientDepartment)
    {
        $data = $request->validate([
            'recorded_at' => 'required|date',
            'kind' => 'required|in:intake,output',
            'category' => 'required|integer',
            'volume_ml' => 'required|numeric|min:0|max:20000',
            'note' => 'nullable|string|max:500',
        ]);
        IntakeOutput::create([
            'ipd_patient_department_id' => $ipdPatientDepartment->id, 'recorded_at' => $data['recorded_at'],
            'intake_type' => $data['kind'] === 'intake' ? $data['category'] : null,
            'output_type' => $data['kind'] === 'output' ? $data['category'] : null,
            'volume_ml' => $data['volume_ml'], 'note' => $data['note'] ?? null, 'recorded_by' => auth()->id(),
        ]);
        Flash::success(__('messages.nursing.flow_saved'));

        return redirect()->to(route('ipd.nursing.show', $ipdPatientDepartment->id) . '#flows');
    }

    public function storeAdministration(Request $request, MedicationAdministration $dose)
    {
        $data = $request->validate(['status' => 'required|integer', 'administered_at' => 'nullable|date', 'note' => 'nullable|string|max:500']);
        $dose->update([
            'status' => $data['status'],
            'administered_at' => $data['administered_at'] ?? now(),
            'note' => $data['note'] ?? null,
            'recorded_by' => auth()->id(),
        ]);
        $warning = $dose->syncStock();
        $warning ? Flash::warning(__('messages.mar_stock.not_taken', ['reason' => $warning])) : Flash::success(__('messages.nursing.dose_saved'));

        return redirect()->to(route('ipd.nursing.show', $dose->ipd_patient_department_id) . '#mar');
    }
}
