@extends('layouts.app')
@section('title')
    {{ __('messages.claims.new') }} · {{ $insurance->name }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('claims.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-5 mb-5">
                    <div><span class="text-muted">{{ __('messages.claims.period') }}:</span> {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</div>
                    <div><span class="text-muted">{{ __('messages.insurance.billing_option') }}:</span> {{ __('messages.insurance.billing_options.' . ($insurance->billing_option ?: 'monthly')) }}</div>
                    <div><span class="text-muted">{{ __('messages.claims.lines') }}:</span> {{ $lines->count() }}</div>
                    <div><span class="text-muted">{{ __('messages.claims.claimed') }}:</span> <b>{{ formatMoney($lines->sum('payer_amount')) }}</b></div>
                </div>
                @include('claims.lines_table', ['rows' => $lines->map(fn ($l) => (object) ($l + ['patient' => $patients[$l['patient_id']] ?? null, 'status' => null]))])
                @if ($lines->isNotEmpty())
                    {{ Form::open(['route' => 'claims.store', 'method' => 'post', 'class' => 'row g-3 justify-content-end align-items-end mt-3']) }}
                    {{ Form::hidden('insurance_id', $insurance->id) }}
                    {{ Form::hidden('from', $from->toDateString()) }}
                    {{ Form::hidden('to', $to->toDateString()) }}
                    <div class="col-md-5">{{ Form::text('note', null, ['class' => 'form-control', 'maxlength' => 500, 'placeholder' => __('messages.stock_transfer.note')]) }}</div>
                    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">{{ __('messages.claims.make') }}</button></div>
                    {{ Form::close() }}
                @endif
            </div>
        </div>
    </div>
@endsection
