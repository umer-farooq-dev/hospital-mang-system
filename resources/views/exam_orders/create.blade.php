@extends('layouts.app')
@section('title')
    {{ __('messages.exam_orders.new') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ request('from') === 'reception-desk' && canAccessRoute('reception-desk.index') ? route('reception-desk.index') : route('exam-orders.index') }}"
               class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('layouts.errors')
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['route' => 'exam-orders.store', 'method' => 'post', 'id' => 'examOrderForm', 'data-quote-url' => route('exam-orders.quote')]) }}
                    {{ Form::hidden('from', request('from')) }}
                    <div class="row">
                        <div class="col-md-4 mb-5">
                            {{ Form::label('patient_id', __('messages.cashier.patient') . ':', ['class' => 'form-label']) }}
                            <span class="required"></span>
                            {{ Form::select('patient_id', $patients, old('patient_id', $patientId), ['class' => 'form-select', 'required', 'id' => 'examPatientId', 'data-control' => 'select2', 'placeholder' => __('messages.document.select_patient')]) }}
                        </div>
                        <div class="col-md-4 mb-5">
                            {{ Form::label('doctor_id', __('messages.exam_orders.prescriber') . ':', ['class' => 'form-label']) }}
                            {{ Form::select('doctor_id', $doctors, old('doctor_id'), ['class' => 'form-select', 'id' => 'examDoctorId', 'data-control' => 'select2', 'placeholder' => __('messages.exam_orders.no_prescriber')]) }}
                        </div>
                        <div class="col-md-4 mb-5">
                            {{ Form::label('patient_insurance_id', __('messages.split.payer') . ':', ['class' => 'form-label']) }}
                            <select name="patient_insurance_id" id="examPolicyId" class="form-select" data-control="select2" data-url="{{ route('opd.payer.options') }}">
                                <option value="">{{ __('messages.patient_insurance.self_pay') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-5">
                            {{ Form::label('price_list_id', __('messages.split.price_list') . ':', ['class' => 'form-label']) }}
                            {{ Form::select('price_list_id', $priceLists, old('price_list_id', $defaultList), ['class' => 'form-select', 'id' => 'examPriceListId', 'data-control' => 'select2']) }}
                        </div>
                        <div class="col-md-4 mb-5">
                            {{ Form::label('voucher_number', __('messages.split.voucher_number') . ':', ['class' => 'form-label']) }}
                            {{ Form::text('voucher_number', old('voucher_number'), ['class' => 'form-control', 'id' => 'examVoucher', 'maxlength' => 50, 'placeholder' => __('messages.split.voucher_hint')]) }}
                        </div>
                        <div class="col-md-12 mb-5">
                            {{ Form::label('tests', __('messages.exam_orders.exams') . ':', ['class' => 'form-label']) }}
                            <span class="required"></span>
                            {{ Form::select('tests[]', $tests, old('tests'), ['class' => 'form-select', 'multiple', 'required', 'id' => 'examTests', 'data-control' => 'select2', 'data-placeholder' => __('messages.exam_orders.choose_exams')]) }}
                        </div>
                        <div class="col-md-12 mb-5" id="examQuote"></div>
                        <div class="col-md-12 mb-5">
                            {{ Form::label('notes', __('messages.cashier.notes') . ':', ['class' => 'form-label']) }}
                            {{ Form::textarea('notes', old('notes'), ['class' => 'form-control', 'rows' => 2, 'maxlength' => 1000]) }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ Form::submit(__('messages.exam_orders.save_and_collect'), ['class' => 'btn btn-primary']) }}
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
