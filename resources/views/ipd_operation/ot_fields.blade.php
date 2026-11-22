{{-- Theatre fields of an operation (shared by the add and edit forms). --}}
<div class="col-12"><hr class="mt-0"><h5 class="mb-4">{{ __('messages.ot.theatre') }}</h5></div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('ot_room', __('messages.ot.room') . ':', ['class' => 'form-label']) }}
    {{ Form::select('ot_room', \App\Models\MasterListItem::options('ot_room'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.ot.room')]) }}
</div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('scheduled_start', __('messages.ot.scheduled_start') . ':', ['class' => 'form-label']) }}
    <input type="datetime-local" name="scheduled_start" class="form-control">
</div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('scheduled_end', __('messages.ot.scheduled_end') . ':', ['class' => 'form-label']) }}
    <input type="datetime-local" name="scheduled_end" class="form-control">
</div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('status', __('messages.ot.status') . ':', ['class' => 'form-label']) }}
    {{ Form::select('status', \App\Models\MasterListItem::options('operation_status'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.ot.status')]) }}
</div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('intervention_type', __('messages.ot.intervention_type') . ':', ['class' => 'form-label']) }}
    {{ Form::select('intervention_type', \App\Models\MasterListItem::options('intervention_type'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.ot.intervention_type')]) }}
</div>
<div class="form-group col-md-4 mb-5">
    {{ Form::label('anesthesia_type', __('messages.ot.anaesthesia_type') . ':', ['class' => 'form-label']) }}
    {{ Form::select('anesthesia_type', \App\Models\MasterListItem::options('anaesthesia_type'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.ot.anaesthesia_type')]) }}
</div>
<div class="form-group col-md-6 mb-5">
    {{ Form::label('assistant_doctor_id', __('messages.ot.assistant_doctor') . ':', ['class' => 'form-label']) }}
    {{ Form::select('assistant_doctor_id', $doctors, null, ['class' => 'form-select', 'placeholder' => __('messages.web_appointment.select_doctor')]) }}
</div>
<div class="form-group col-md-6 mb-5">
    {{ Form::label('anaesthetist_doctor_id', __('messages.ot.anaesthetist_doctor') . ':', ['class' => 'form-label']) }}
    {{ Form::select('anaesthetist_doctor_id', $doctors, null, ['class' => 'form-select', 'placeholder' => __('messages.web_appointment.select_doctor')]) }}
</div>
<div class="form-group col-12 mb-5">
    <label class="form-label">{{ __('messages.ot.preop_checklist') }}:</label>
    <input type="hidden" name="preop_checks[]" value="">
    <div class="d-flex flex-wrap gap-5">
        @forelse (\App\Models\MasterListItem::options('preop_check') as $code => $label)
            <label class="form-check form-check-sm form-check-custom">
                <input class="form-check-input" type="checkbox" name="preop_checks[]" value="{{ $code }}">
                <span class="form-check-label">{{ $label }}</span>
            </label>
        @empty
            <span class="text-muted fs-7">{{ __('messages.ot.no_preop_items') }}</span>
        @endforelse
    </div>
</div>
<div class="col-12"><hr class="mt-0"></div>
