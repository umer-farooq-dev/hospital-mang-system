{{-- Where the patient comes from, emergency level and referrer (Master Lists). --}}
@foreach (\App\Models\OpdPatientDepartment::VISIT_LISTS as $list)
    <div class="col-md-4">
        <div class="mb-5">
            {{ Form::label($list, __('messages.opd_patient.' . $list) . ':', ['class' => 'form-label']) }}
            {{ Form::select($list, \App\Models\MasterListItem::options($list, isset($opdPatientDepartment) ? $opdPatientDepartment->$list : null), null, ['class' => 'form-select', 'data-control' => 'select2', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.opd_patient.' . $list)]) }}
        </div>
    </div>
@endforeach
