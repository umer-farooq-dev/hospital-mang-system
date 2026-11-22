@extends('layouts.app')
@section('title')
    {{ __('messages.claims.invoice') }} {{ $invoice->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid d-print-none">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') @include('claims.status', ['status' => $invoice->status])</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                @if ($invoice->status === 'issued' && canAccessRoute('claims.cancel'))
                    {{ Form::open(['route' => ['claims.cancel', $invoice->id], 'method' => 'post', 'class' => 'd-inline']) }}
                    <button type="submit" class="btn btn-light-danger ms-2">{{ __('messages.stock_transfer.cancel') }}</button>
                    {{ Form::close() }}
                @endif
                <a href="{{ route('claims.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card mb-7">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap mb-7">
                    <div>
                        <div class="fs-3 fw-bold">{{ getAppName() }}</div>
                        <div class="text-muted">{{ __('messages.claims.invoice') }} N° <b>{{ $invoice->number }}</b></div>
                        <div class="text-muted">{{ __('messages.claims.issued_on') }}: {{ $invoice->issued_on->format('d/m/Y') }}
                            @if ($invoice->due_on) · {{ __('messages.claims.due_on') }}: {{ $invoice->due_on->format('d/m/Y') }}@endif</div>
                        <div class="text-muted">{{ __('messages.claims.period') }}: {{ $invoice->period_from->format('d/m/Y') }} – {{ $invoice->period_to->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">{{ $invoice->insurance->name ?? '' }}</div>
                        @if ($invoice->insurance?->address)<div class="text-muted" style="white-space: pre-line">{{ $invoice->insurance->address }}</div>@endif
                        @if ($invoice->insurance?->phone)<div class="text-muted">{{ $invoice->insurance->phone }}</div>@endif
                    </div>
                </div>
                @include('claims.lines_table', ['rows' => $invoice->lines])
                @if ($invoice->insurance?->claim_note)
                    <div class="mt-5" style="white-space: pre-line">{{ $invoice->insurance->claim_note }}</div>
                @endif
                @if ($invoice->note)
                    <div class="mt-3 text-muted">{{ $invoice->note }}</div>
                @endif
                <div class="mt-5 text-muted fs-7 d-print-none">{{ __('messages.claims.made_by', ['name' => $invoice->author->full_name ?? '', 'date' => $invoice->created_at->format('d/m/Y H:i')]) }}</div>
            </div>
        </div>
    </div>
@endsection
