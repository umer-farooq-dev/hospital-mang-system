{{-- ICD-10 code and status of a diagnosis (shared by the IPD and OPD diagnosis forms). --}}
<div class="form-group col-sm-8 mb-5">
    {{ Form::label('icd10_code', __('messages.icd10.code') . ':', ['class' => 'form-label']) }}
    <select name="icd10_code" class="form-select icd10-select" data-url="{{ route('icd10.search') }}" data-placeholder="{{ __('messages.icd10.search_placeholder') }}"></select>
</div>
<div class="form-group col-sm-4 mb-5">
    {{ Form::label('diagnosis_status', __('messages.icd10.status') . ':', ['class' => 'form-label']) }}
    {{ Form::select('diagnosis_status', \App\Models\MasterListItem::options('diagnosis_status'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}
</div>
