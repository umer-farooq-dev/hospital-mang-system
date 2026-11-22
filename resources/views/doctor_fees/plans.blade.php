@extends('layouts.app')
@section('title')
    {{ __('messages.doctor_fees.plans') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('doctor-fees.index') }}" class="btn btn-outline-primary">{{ __('messages.doctor_fees.statements') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('doctor-fees.plans.update');
        $off = $canEdit ? [] : ['disabled' => 'disabled'];
        $types = collect(\App\Models\DoctorFeePlan::PAY_TYPES)->mapWithKeys(fn ($t) => [$t => __('messages.doctor_fees.pay_types.' . $t)])->all();
        $rows = array_merge($withholdings, array_fill(0, 3, ['name' => '', 'percent' => '']));
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        {{ Form::open(['route' => 'doctor-fees.plans.update', 'method' => 'post']) }}
        <div class="card mb-7">
            <div class="card-body">
                <div class="form-text mb-4">{{ __('messages.doctor_fees.plans_hint') }}</div>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.doctor_fees.doctor') }}</th>
                            <th class="w-250px">{{ __('messages.doctor_fees.pay_type') }}</th>
                            <th class="w-150px">{{ __('messages.doctor_fees.rate') }}</th>
                            <th>{{ __('messages.doctor_fees.exempt') }}</th>
                        </tr>
                    </thead>
                    @foreach ($doctors as $id => $name)
                        @php
                            $plan = $plans[$id] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $name }}</td>
                            <td>{{ Form::select('plans[' . $id . '][pay_type]', $types, $plan->pay_type ?? 'share', ['class' => 'form-select form-select-sm'] + $off) }}</td>
                            <td>{{ Form::number('plans[' . $id . '][rate]', $plan->rate ?? 0, ['class' => 'form-control form-control-sm', 'min' => 0, 'step' => '0.01'] + $off) }}</td>
                            <td><input type="checkbox" class="form-check-input" name="plans[{{ $id }}][withholding_exempt]" value="1" {{ $plan?->withholding_exempt ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.doctor_fees.withholdings') }}</h5>
                <div class="form-text mb-4">{{ __('messages.doctor_fees.withholdings_hint') }}</div>
                <div class="row g-3">
                    @foreach ($rows as $i => $row)
                        <div class="col-md-4">{{ Form::text('withholdings[' . $i . '][name]', $row['name'], ['class' => 'form-control', 'maxlength' => 60, 'placeholder' => __('messages.doctor_fees.withholding_name')] + $off) }}</div>
                        <div class="col-md-2">{{ Form::number('withholdings[' . $i . '][percent]', $row['percent'], ['class' => 'form-control', 'min' => 0, 'max' => 100, 'step' => '0.01', 'placeholder' => '%'] + $off) }}</div>
                    @endforeach
                    <div class="col-md-3">
                        {{ Form::label('doctor_statement_prefix', __('messages.doctor_fees.prefix') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('doctor_statement_prefix', $prefix, ['class' => 'form-control', 'maxlength' => 10, 'required'] + $off) }}
                    </div>
                </div>
                @if ($canEdit)
                    <div class="d-flex justify-content-end mt-5">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
            </div>
        </div>
        {{ Form::close() }}
    </div>
@endsection
