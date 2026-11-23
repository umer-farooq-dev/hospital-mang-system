@extends('layouts.app')
@section('title')
    {{ __('messages.posting.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('accounting.posting.update');
        $off = $canEdit ? [] : ['disabled' => 'disabled'];
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        @canroute('accounting.posting.generate')
            <div class="card mb-7">
                <div class="card-body">
                    <h5 class="mb-2">{{ __('messages.posting.generate') }}</h5>
                    <div class="form-text mb-4">{{ __('messages.posting.generate_hint') }}</div>
                    {{ Form::open(['route' => 'accounting.posting.generate', 'method' => 'post', 'class' => 'row g-3 align-items-end']) }}
                    <div class="col-md-3"><label class="form-label">{{ __('messages.stock_report.from') }}</label><input type="date" name="from" class="form-control" required value="{{ $summary['from'] ?? now()->startOfMonth()->toDateString() }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('messages.stock_report.to') }}</label><input type="date" name="to" class="form-control" required value="{{ $summary['to'] ?? now()->toDateString() }}"></div>
                    <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('messages.posting.run') }}</button></div>
                    {{ Form::close() }}
                    @if ($summary && $summary['errors'])
                        <div class="mt-5">
                            <div class="fw-bold text-danger mb-2">{{ __('messages.posting.errors') }}</div>
                            @foreach ($summary['errors'] as $error)
                                <div class="text-danger fs-7">{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endcanroute
        {{ Form::open(['route' => 'accounting.posting.update', 'method' => 'post']) }}
        <div class="card mb-7">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.posting.accounts') }}</h5>
                <div class="form-text mb-4">{{ __('messages.posting.accounts_hint') }}</div>
                <div class="row g-3">
                    @foreach ($keys as $key => $label)
                        <div class="col-md-6 d-flex align-items-center gap-3">
                            <span class="w-50 fs-7">{{ $label }}</span>
                            {{ Form::select('accounts[' . $key . ']', $accounts, $map[$key] ?? null, ['class' => 'form-select form-select-sm w-50',
                                'placeholder' => isset($defaults[$key]) && $defaults[$key] ? __('messages.posting.default', ['account' => $accounts[$defaults[$key]] ?? '']) : __('messages.posting.none')] + $off) }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.posting.journals') }}</h5>
                <div class="form-text mb-4">{{ __('messages.posting.journals_hint') }}</div>
                <div class="row g-3">
                    @foreach ($journalKeys as $key => $label)
                        <div class="col-md-6 d-flex align-items-center gap-3">
                            <span class="w-50 fs-7">{{ $label }}</span>
                            {{ Form::select('journals[' . $key . ']', $journals, $journalMap[$key] ?? null, ['class' => 'form-select form-select-sm w-50',
                                'placeholder' => isset($journalDefaults[$key]) && $journalDefaults[$key] ? __('messages.posting.default', ['account' => $journals[$journalDefaults[$key]] ?? '']) : __('messages.posting.same_as_receipts')] + $off) }}
                        </div>
                    @endforeach
                </div>
                @if ($canEdit)
                    <div class="d-flex justify-content-end mt-5">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
            </div>
        </div>
        {{ Form::close() }}
    </div>
@endsection
