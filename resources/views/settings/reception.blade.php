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

        <div class="col-md-12 mb-5">
            <div class="d-flex align-items-center">
                <span class="form-label mb-0">{{ __('messages.reception.webcam') }}:</span>
                <label class="form-check form-switch form-switch-sm ms-3">
                    <input type="checkbox" name="reception_webcam" class="form-check-input" value="1"
                        {{ $reception['webcam'] ? 'checked' : '' }}>
                    <span class="custom-switch-indicator"></span>
                </label>
            </div>
            <div class="text-muted fs-7">{{ __('messages.reception.webcam_hint') }}</div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="d-flex align-items-center">
                <span class="form-label mb-0">{{ __('messages.reception.id_scan') }}:</span>
                <label class="form-check form-switch form-switch-sm ms-3">
                    <input type="checkbox" name="reception_id_scan" class="form-check-input" value="1" {{ $reception['id_scan'] ? 'checked' : '' }}>
                    <span class="custom-switch-indicator"></span>
                </label>
            </div>
            <div class="text-muted fs-7">{{ __('messages.reception.id_scan_hint') }}</div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="d-flex align-items-center">
                <span class="form-label mb-0">{{ __('messages.reception.mrz_scanner') }}:</span>
                <label class="form-check form-switch form-switch-sm ms-3">
                    <input type="checkbox" name="reception_mrz_scanner" class="form-check-input" value="1" {{ $reception['mrz_scanner'] ? 'checked' : '' }}>
                    <span class="custom-switch-indicator"></span>
                </label>
            </div>
            <div class="text-muted fs-7">{{ __('messages.reception.mrz_scanner_hint') }}</div>
        </div>
        <div class="col-md-4 mb-3">
            {{ Form::label('default_payer', __('messages.reception.default_payer') . ':', ['class' => 'form-label']) }}
            {{ Form::select('default_payer', ['self' => __('messages.reception.default_payers.self'), 'policy' => __('messages.reception.default_payers.policy')], $reception['default_payer'], ['class' => 'form-select']) }}
        </div>
        <div class="col-md-4 mb-3">
            {{ Form::label('id_scan_document_type', __('messages.reception.id_scan_document_type') . ':', ['class' => 'form-label']) }}
            {{ Form::select('id_scan_document_type', \App\Models\MasterListItem::options('id_document_type', $reception['id_scan_document_type']), $reception['id_scan_document_type'], ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}
        </div>
        <div class="col-md-4 mb-3">
            {{ Form::label('id_scan_save_as', __('messages.reception.id_scan_save_as') . ':', ['class' => 'form-label']) }}
            {{ Form::select('id_scan_save_as', \App\Models\DocumentType::orderBy('name')->pluck('name', 'id'), $reception['id_scan_save_as'], ['class' => 'form-select', 'placeholder' => __('messages.reception.id_scan_no_image')]) }}
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

        <div class="col-md-12 mb-3 mt-3">
            <h5>{{ __('messages.reception.queue') }}</h5>
        </div>
        <div class="col-md-6 mb-5">
            {{ Form::label('queue_card_count', __('messages.reception.queue_card_count') . ':', ['class' => 'form-label']) }}
            {{ Form::number('queue_card_count', \App\Models\PatientQueue::cardCount(), ['class' => 'form-control', 'min' => 0, 'max' => 9999, 'required']) }}
            <div class="text-muted fs-7">{{ __('messages.reception.queue_card_count_hint') }}</div>
        </div>

        @canroute('master-lists.index')
        <div class="col-md-12 mb-5 d-flex flex-wrap gap-6">
            <a href="{{ route('master-lists.index', ['type' => 'id_document_type']) }}" class="text-decoration-none">
                <i class="fas fa-list me-1"></i>{{ __('messages.reception.manage_id_types') }}
            </a>
            @foreach (\App\Models\OpdPatientDepartment::VISIT_LISTS as $list)
                <a href="{{ route('master-lists.index', ['type' => $list]) }}" class="text-decoration-none">
                    <i class="fas fa-list me-1"></i>{{ __('messages.master_list.types.' . $list) }}
                </a>
            @endforeach
        </div>
        @endcanroute
    </div>
    <div class="d-flex justify-content-end">
        {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-3']) }}
        {{ Form::reset(__('messages.common.cancel'), ['class' => 'btn btn-secondary me-2']) }}
    </div>
    {{ Form::close() }}
@endsection
