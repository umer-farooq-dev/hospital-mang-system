@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.turnover') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <button type="button" class="btn btn-outline-primary d-print-none" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="card mb-7">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="form-text mb-4">{{ __('messages.accounting.turnover_hint') }}</div>
                <h5>{{ __('messages.accounting.by_speciality') }}</h5>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.accounting.speciality') }}</th><th class="text-end">{{ __('messages.accounting.consultations') }}</th><th class="text-end">{{ __('messages.accounting.exams') }}</th><th class="text-end">{{ __('messages.accounting.turnover_amount') }}</th></tr></thead>
                    @foreach ($specialities as $s)
                        <tr><td>{{ $s['speciality'] }}</td><td class="text-end">{{ $s['consultations'] }}</td><td class="text-end">{{ $s['exams'] }}</td><td class="text-end fw-bold">{{ formatMoney($s['amount']) }}</td></tr>
                    @endforeach
                    <tfoot><tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ $specialities->sum('consultations') }}</td><td class="text-end">{{ $specialities->sum('exams') }}</td><td class="text-end">{{ formatMoney($specialities->sum('amount')) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5>{{ __('messages.accounting.by_doctor') }}</h5>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.doctor_fees.doctor') }}</th><th>{{ __('messages.accounting.speciality') }}</th><th class="text-end">{{ __('messages.accounting.consultations') }}</th><th class="text-end">{{ __('messages.claims.total') }}</th><th class="text-end">{{ __('messages.accounting.exams') }}</th><th class="text-end">{{ __('messages.claims.total') }}</th><th class="text-end">{{ __('messages.accounting.turnover_amount') }}</th></tr></thead>
                    @forelse ($rows as $r)
                        <tr><td>{{ $r['doctor'] }}</td><td>{{ $r['speciality'] }}</td><td class="text-end">{{ $r['consultations'] }}</td><td class="text-end">{{ formatMoney($r['consultation_amount']) }}</td>
                            <td class="text-end">{{ $r['exams'] }}</td><td class="text-end">{{ formatMoney($r['exam_amount']) }}</td><td class="text-end fw-bold">{{ formatMoney($r['consultation_amount'] + $r['exam_amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('messages.stock_report.empty') }}</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
@endsection
