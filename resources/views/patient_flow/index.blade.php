@extends('layouts.app')
@section('title')
    {{ __('messages.patient_flow.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('cashier.nav')
        </div>
    </div>
@endsection
@section('content')
    @php
        $query = array_filter(['from' => $filters['from']->toDateString(), 'to' => $filters['to']->toDateString(), 'department' => $filters['department']]);
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('patient-flow.index') }}" class="row g-3 align-items-end mb-5">
                    <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                    <div class="col-md-3"><label class="form-label">{{ __('messages.reception_report.from') }}</label><input type="date" name="from" class="form-control" value="{{ $query['from'] }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('messages.reception_report.to') }}</label><input type="date" name="to" class="form-control" value="{{ $query['to'] }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('messages.patient_flow.service') }}</label>
                        {{ Form::select('department', $departments, $filters['department'], ['class' => 'form-select', 'placeholder' => __('messages.filter.All')]) }}</div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <a href="{{ route('patient-flow.print', $query + ['tab' => $filters['tab']]) }}" target="_blank" class="btn btn-light-primary"><i class="fas fa-print"></i></a>
                    </div>
                </form>
                <ul class="nav nav-tabs mb-5 pb-1 overflow-auto flex-nowrap text-nowrap">
                    @foreach (\App\Repositories\PatientFlowRepository::TABS as $tab)
                        <li class="nav-item me-7 mb-3"><a class="nav-link p-0 {{ $filters['tab'] === $tab ? 'active' : '' }}" href="{{ route('patient-flow.index', $query + ['tab' => $tab]) }}">{{ __('messages.patient_flow.tabs.' . $tab) }}</a></li>
                    @endforeach
                </ul>
                <div class="table-responsive">@include('patient_flow.table')</div>
            </div>
        </div>
    </div>
@endsection
