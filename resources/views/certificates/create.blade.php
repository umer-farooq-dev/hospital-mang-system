@extends('layouts.app')
@section('title')
    {{ __('messages.certificate.new') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('certificates.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'certificates.store', 'method' => 'post', 'id' => 'certificateForm', 'data-preview-url' => route('certificates.preview')]) }}
                {{ Form::hidden('ipd_patient_department_id', old('ipd_patient_department_id', $ipdId)) }}
                <div class="row">
                    <div class="col-md-4 mb-5">
                        {{ Form::label('certificate_template_id', __('messages.certificate.template') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('certificate_template_id', $templates, old('certificate_template_id'), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                    </div>
                    <div class="col-md-4 mb-5">
                        {{ Form::label('patient_id', __('messages.cashier.patient') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('patient_id', $patients, old('patient_id', $patientId), ['class' => 'form-select', 'required', 'data-control' => 'select2', 'placeholder' => __('messages.document.select_patient')]) }}
                    </div>
                    <div class="col-md-4 mb-5">
                        {{ Form::label('doctor_id', __('messages.certificate.doctor') . ':', ['class' => 'form-label']) }}
                        {{ Form::select('doctor_id', $doctors, old('doctor_id', $doctorId), ['class' => 'form-select', 'data-control' => 'select2', 'placeholder' => __('messages.web_appointment.select_doctor')]) }}
                    </div>
                    <div class="col-md-3 mb-5">
                        {{ Form::label('start_date', __('messages.certificate.start_date') . ':', ['class' => 'form-label']) }}
                        <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-5">
                        {{ Form::label('end_date', __('messages.certificate.end_date') . ':', ['class' => 'form-label']) }}
                        <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                    </div>
                    <div class="col-md-6 mb-5">
                        {{ Form::label('notes', __('messages.certificate.notes') . ':', ['class' => 'form-label']) }}
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" maxlength="1000" placeholder="{{ __('messages.certificate.notes_hint') }}">
                    </div>
                    <div class="col-12 mb-2 d-flex justify-content-between align-items-end">
                        {{ Form::label('body', __('messages.certificate.text') . ':', ['class' => 'form-label required mb-0']) }}
                        <button type="button" class="btn btn-sm btn-light-primary" id="certificateFill"><i class="fas fa-sync me-1"></i>{{ __('messages.certificate.fill') }}</button>
                    </div>
                    <div class="col-12 mb-5">
                        {{ Form::textarea('body', old('body'), ['class' => 'form-control', 'rows' => 14, 'maxlength' => 20000, 'required', 'id' => 'certificateBody']) }}
                        <div class="form-text">{{ __('messages.certificate.text_hint') }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">{{ Form::submit(__('messages.certificate.issue'), ['class' => 'btn btn-primary']) }}</div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
