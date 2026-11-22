@extends('layouts.app')
@section('title')
    {{ __('messages.claims.claims') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        @canroute('claims.store')
            <div class="card mb-7">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.claims.new') }}</h5>
                    <form method="get" action="{{ route('claims.create') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('messages.claims.payer') }}</label>
                            {{ Form::select('insurance_id', $insurances, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                        </div>
                        <div class="col-md-3"><label class="form-label required">{{ __('messages.stock_report.from') }}</label><input type="date" name="from" class="form-control" required value="{{ now()->subMonthNoOverflow()->startOfMonth()->toDateString() }}"></div>
                        <div class="col-md-3"><label class="form-label required">{{ __('messages.stock_report.to') }}</label><input type="date" name="to" class="form-control" required value="{{ now()->subMonthNoOverflow()->endOfMonth()->toDateString() }}"></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.claims.preview') }}</button></div>
                        <div class="col-12 form-text">{{ __('messages.claims.new_hint') }}</div>
                    </form>
                </div>
            </div>
        @endcanroute
        <div class="card mb-7">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('insurance_id', $insurances, request('insurance_id'), ['class' => 'form-select', 'placeholder' => __('messages.claims.all_payers')]) }}</div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">{{ __('messages.stock_transfer.all_statuses') }}</option>
                            @foreach (\App\Models\ClaimInvoice::STATUSES as $option)
                                <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ __('messages.claims.statuses.' . $option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.claims.number') }}</th>
                                <th>{{ __('messages.claims.payer') }}</th>
                                <th>{{ __('messages.claims.period') }}</th>
                                <th>{{ __('messages.claims.issued_on') }}</th>
                                <th>{{ __('messages.claims.due_on') }}</th>
                                <th class="text-end">{{ __('messages.claims.claimed') }}</th>
                                <th class="text-end">{{ __('messages.claims.outstanding') }}</th>
                                <th>{{ __('messages.stock_transfer.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('claims.show', $invoice->id) }}">{{ $invoice->number }}</a></td>
                                    <td>{{ $invoice->insurance->name ?? '' }}</td>
                                    <td>{{ $invoice->period_from->format('d/m/Y') }} – {{ $invoice->period_to->format('d/m/Y') }}</td>
                                    <td>{{ $invoice->issued_on->format('d/m/Y') }}</td>
                                    <td class="{{ $invoice->due_on && $invoice->due_on->isPast() && $invoice->outstanding() > 0 ? 'text-danger fw-bold' : '' }}">{{ $invoice->due_on?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end">{{ formatMoney($invoice->total_claimed) }}</td>
                                    <td class="text-end">{{ formatMoney($invoice->outstanding()) }}</td>
                                    <td>@include('claims.status', ['status' => $invoice->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('messages.claims.none') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $invoices->links() }}
            </div>
        </div>
        @canroute('claims.settings')
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.claims.numbering') }}</h5>
                    {{ Form::open(['route' => 'claims.settings', 'method' => 'post', 'class' => 'row g-3 align-items-end']) }}
                    <div class="col-md-4">
                        {{ Form::label('claim_invoice_format', __('messages.claims.format') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('claim_invoice_format', $settings['claim_invoice_format'] ?? '{seq}/{mm}/{yy}', ['class' => 'form-control', 'maxlength' => 60, 'required']) }}
                    </div>
                    <div class="col-md-2">
                        {{ Form::label('claim_invoice_digits', __('messages.claims.digits') . ':', ['class' => 'form-label']) }}
                        {{ Form::number('claim_invoice_digits', $settings['claim_invoice_digits'] ?? 4, ['class' => 'form-control', 'min' => 1, 'max' => 8, 'required']) }}
                    </div>
                    <div class="col-md-2">
                        {{ Form::label('proforma_validity_days', __('messages.proforma.validity_days') . ':', ['class' => 'form-label']) }}
                        {{ Form::number('proforma_validity_days', $settings['proforma_validity_days'] ?? 30, ['class' => 'form-control', 'min' => 0, 'max' => 3650, 'required']) }}
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.save') }}</button></div>
                    <div class="col-12 form-text">{{ __('messages.claims.format_hint') }}</div>
                    {{ Form::close() }}
                </div>
            </div>
        @endcanroute
    </div>
@endsection
