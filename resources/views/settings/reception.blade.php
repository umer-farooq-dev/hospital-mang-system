@extends('settings.edit')
@section('title')
    {{ __('messages.reception.reception') }}
@endsection
@section('section')
    @php
        $reception = \App\Models\Patient::registrationSettings();
        $fieldLabels = [
            'email' => __('messages.user.email'),
            'phone' => __('messages.user.phone'),
            'dob' => __('messages.user.dob'),
            'id_document' => __('messages.reception.id_document'),
            'address1' => __('messages.user.address1'),
            'city' => __('messages.user.city'),
        ];
    @endphp
    {{ Form::open(['route' => 'settings.reception.update', 'method' => 'post', 'id' => 'receptionSettingForm']) }}
    <div class="row">
        <div class="col-md-12 mb-3">
            <h5>{{ __('messages.reception.patient_registration') }}</h5>
        </div>

        <div class="col-md-12 mb-5">
            <label class="form-label">{{ __('messages.reception.required_fields') }}:</label>
            <div class="d-flex flex-wrap">
                @foreach (\App\Models\Patient::REQUIRABLE_FIELDS as $field)
                    <label class="form-check form-check-custom me-8 mb-3">
                        <input type="checkbox" class="form-check-input" name="patient_required_fields[]"
                            value="{{ $field }}" {{ in_array($field, $reception['required_fields']) ? 'checked' : '' }}>
                        <span class="form-check-label ms-2">{{ $fieldLabels[$field] }}</span>
                    </label>
                @endforeach
            </div>
            <div class="text-muted fs-7">{{ __('messages.reception.required_fields_hint') }}</div>
        </div>

        <div class="col-md-12 mb-5">
            <div class="d-flex align-items-center">
                <span class="form-label mb-0">{{ __('messages.reception.patient_portal_login') }}:</span>
                <label class="form-check form-switch form-switch-sm ms-3">
                    <input type="checkbox" name="patient_portal_login" class="form-check-input" value="1"
                        {{ $reception['portal_login'] ? 'checked' : '' }}>
                    <span class="custom-switch-indicator"></span>
                </label>
            </div>
            <div class="text-muted fs-7">{{ __('messages.reception.patient_portal_login_hint') }}</div>
        </div>

        <div class="col-md-12 mb-3 mt-3">
            <h5>{{ __('messages.reception.duplicate_check') }}</h5>
        </div>
        <div class="col-md-6 mb-5">
            {{ Form::label('patient_duplicate_check', __('messages.reception.duplicate_action') . ':', ['class' => 'form-label']) }}
            {{ Form::select('patient_duplicate_check', collect(\App\Models\Patient::DUPLICATE_MODES)->mapWithKeys(fn ($mode) => [$mode => __('messages.reception.duplicate_' . $mode)]), $reception['duplicate_check'], ['class' => 'form-select', 'data-control' => 'select2']) }}
        </div>
        <div class="col-md-6 mb-5">
            <label class="form-label">{{ __('messages.reception.duplicate_fields') }}:</label>
            <div class="d-flex flex-wrap">
                @foreach (\App\Models\Patient::DUPLICATE_FIELDS as $field)
                    <label class="form-check form-check-custom me-8 mb-3">
                        <input type="checkbox" class="form-check-input" name="patient_duplicate_fields[]"
                            value="{{ $field }}" {{ in_array($field, $reception['duplicate_fields']) ? 'checked' : '' }}>
                        <span class="form-check-label ms-2">{{ __('messages.reception.match_' . $field) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        @canroute('master-lists.index')
        <div class="col-md-12 mb-5">
            <a href="{{ route('master-lists.index', ['type' => 'id_document_type']) }}" class="text-decoration-none">
                <i class="fas fa-list me-1"></i>{{ __('messages.reception.manage_id_types') }}
            </a>
        </div>
        @endcanroute
    </div>
    <div class="d-flex justify-content-end">
        {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-3']) }}
        {{ Form::reset(__('messages.common.cancel'), ['class' => 'btn btn-secondary me-2']) }}
    </div>
    {{ Form::close() }}
@endsection
