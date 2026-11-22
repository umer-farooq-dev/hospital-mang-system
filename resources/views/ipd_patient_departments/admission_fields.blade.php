{{-- Package and guardian of the admission (moved from the old Patient Admissions screen). --}}
@php($admissionRow = $ipdPatientDepartment ?? null)
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('package_id', __('messages.ipd_patient.package') . ':', ['class' => 'form-label']) }}
        {{ Form::select('package_id', \App\Models\Package::orderBy('name')->pluck('name', 'id'), $admissionRow->package_id ?? null, ['class' => 'form-select', 'data-control' => 'select2', 'placeholder' => __('messages.ipd_patient.no_package')]) }}
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('guardian_name', __('messages.ipd_patient.guardian_name') . ':', ['class' => 'form-label']) }}
        {{ Form::text('guardian_name', $admissionRow->guardian_name ?? null, ['class' => 'form-control', 'maxlength' => 191]) }}
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('guardian_relation', __('messages.ipd_patient.guardian_relation') . ':', ['class' => 'form-label']) }}
        {{ Form::select('guardian_relation', \App\Models\MasterListItem::options('guardian_relation', $admissionRow->guardian_relation ?? null), $admissionRow->guardian_relation ?? null, ['class' => 'form-select', 'data-control' => 'select2', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.ipd_patient.guardian_relation')]) }}
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('guardian_contact', __('messages.ipd_patient.guardian_contact') . ':', ['class' => 'form-label']) }}
        {{ Form::text('guardian_contact', $admissionRow->guardian_contact ?? null, ['class' => 'form-control', 'maxlength' => 50]) }}
    </div>
</div>
<div class="col-md-8">
    <div class="mb-5">
        {{ Form::label('guardian_address', __('messages.ipd_patient.guardian_address') . ':', ['class' => 'form-label']) }}
        {{ Form::text('guardian_address', $admissionRow->guardian_address ?? null, ['class' => 'form-control', 'maxlength' => 500]) }}
    </div>
</div>
