@extends('layouts.app')
@section('title')
    {{ __('messages.icd10.list') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') <span class="text-muted fs-5">({{ number_format($total) }})</span></h1>
        </div>
    </div>
@endsection
@section('content')
    @php($canEdit = canAccessRoute('icd10.update'))
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        @if ($canEdit)
            <div class="row">
                <div class="col-xl-6 mb-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="mb-3">{{ __('messages.icd10.import') }}</h3>
                            <div class="text-muted fs-7 mb-4">{{ __('messages.icd10.import_hint') }}</div>
                            {{ Form::open(['route' => 'icd10.import', 'method' => 'post', 'files' => true, 'class' => 'd-flex gap-3']) }}
                            <input type="file" name="file" accept=".csv,.txt" class="form-control" required>
                            {{ Form::submit(__('messages.icd10.import_button'), ['class' => 'btn btn-primary']) }}
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 mb-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="mb-4">{{ __('messages.icd10.add') }}</h3>
                            {{ Form::open(['route' => 'icd10.store', 'method' => 'post', 'class' => 'row g-3']) }}
                            <div class="col-md-3"><input type="text" name="code" class="form-control" maxlength="10" placeholder="A01.0" required></div>
                            <div class="col-md-6"><input type="text" name="title" class="form-control" maxlength="255" placeholder="{{ __('messages.icd10.title') }}" required></div>
                            <div class="col-md-3">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary w-100']) }}</div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'icd10.index', 'method' => 'get', 'class' => 'mb-5']) }}
                <input type="search" name="search" value="{{ $search }}" class="form-control w-md-400px" placeholder="{{ __('messages.icd10.search_placeholder') }}">
                {{ Form::close() }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th style="width:110px">{{ __('messages.icd10.code') }}</th><th>{{ __('messages.icd10.title') }}</th><th class="text-center" style="width:90px">{{ __('messages.common.active') }}</th><th style="width:90px"></th></tr></thead>
                        <tbody>
                            @forelse ($codes as $code)
                                <tr>
                                    {{ Form::open(['route' => ['icd10.update', $code->id], 'method' => 'post']) }}
                                    <td class="fw-bold">{{ $code->code }}</td>
                                    <td><input type="text" name="title" value="{{ $code->title }}" class="form-control form-control-sm" maxlength="255" {{ $canEdit ? '' : 'disabled' }}></td>
                                    <td class="text-center"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $code->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}></td>
                                    <td>@if ($canEdit){{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-sm btn-light-primary']) }}@endif</td>
                                    {{ Form::close() }}
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">{{ __('messages.icd10.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $codes->links() }}
            </div>
        </div>
    </div>
@endsection
