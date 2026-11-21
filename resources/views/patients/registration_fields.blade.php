{{--
    Patient fields controlled by Settings > Reception, shared by the patient create/edit forms
    and the quick "new patient" modals. Pass $patient when editing.
--}}
@php
    $reception = \App\Models\Patient::registrationSettings();
    $editing = isset($patient) && $patient;
    $currentIdType = $editing ? $patient->id_document_type : null;
@endphp
@if ($reception['duplicate_check'] !== 'off' && canAccessRoute('patients.duplicates'))
    <div class="col-12 d-none patient-duplicate-box" data-url="{{ route('patients.duplicates') }}"
        data-patient-id="{{ $editing ? $patient->id : '' }}">
        <div class="alert {{ $reception['duplicate_check'] === 'block' ? 'alert-danger' : 'alert-warning' }} mb-5">
            <div class="fw-bold mb-2">{{ __('messages.reception.possible_duplicates') }}</div>
            <ul class="mb-0 patient-duplicate-list"></ul>
        </div>
    </div>
@endif
<div class="col-md-6">
    <div class="form-group mb-5">
        {{ Form::label('email', __('messages.user.email') . ':', ['class' => 'form-label']) }}
        @if (\App\Models\Patient::isRequired('email'))
            <span class="required"></span>
        @endif
        {{ Form::email('email', null, ['class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => __('messages.user.email'), \App\Models\Patient::isRequired('email') ? 'required' : '']) }}
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-5">
        {{ Form::label('id_document_type', __('messages.reception.id_document_type') . ':', ['class' => 'form-label']) }}
        @if (\App\Models\Patient::isRequired('id_document'))
            <span class="required"></span>
        @endif
        {{ Form::select('id_document_type', \App\Models\MasterListItem::options('id_document_type', $currentIdType), $currentIdType, ['class' => 'form-select', 'placeholder' => __('messages.reception.select_id_type'), 'data-control' => 'select2', \App\Models\Patient::isRequired('id_document') ? 'required' : '']) }}
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-5">
        {{ Form::label('id_document_number', __('messages.reception.id_document_number') . ':', ['class' => 'form-label']) }}
        @if (\App\Models\Patient::isRequired('id_document'))
            <span class="required"></span>
        @endif
        {{ Form::text('id_document_number', $editing ? $patient->id_document_number : null, ['class' => 'form-control patient-duplicate-field', 'maxlength' => 50, 'autocomplete' => 'off', 'placeholder' => __('messages.reception.id_document_number'), \App\Models\Patient::isRequired('id_document') ? 'required' : '']) }}
    </div>
</div>
@if (! $editing && $reception['portal_login'])
    <div class="col-md-3">
        <div class="form-group mb-5">
            {{ Form::label('password', __('messages.user.password') . ':', ['class' => 'form-label']) }}
            {{ Form::password('password', ['class' => 'form-control', 'min' => '6', 'max' => '10', 'placeholder' => __('messages.user.password'), 'autocomplete' => 'new-password']) }}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-5">
            {{ Form::label('password_confirmation', __('messages.user.password_confirmation') . ':', ['class' => 'form-label']) }}
            {{ Form::password('password_confirmation', ['class' => 'form-control', 'min' => '6', 'max' => '10', 'placeholder' => __('messages.user.password_confirmation'), 'autocomplete' => 'new-password']) }}
        </div>
    </div>
    <div class="col-md-6 mb-5 d-flex align-items-center">
        <span class="text-muted fs-7">{{ __('messages.reception.password_hint') }}</span>
    </div>
@endif
