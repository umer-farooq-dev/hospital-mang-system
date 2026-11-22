@extends('layouts.app')
@section('title')
    {{ __('messages.medicine_import.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('medicines.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <div class="form-text mb-3">{{ __('messages.medicine_import.hint') }}</div>
                <div class="mb-5">
                    @foreach ($columns as $column)
                        <code class="me-2">{{ $column }}</code>
                    @endforeach
                </div>
                {{ Form::open(['route' => 'medicines.import.store', 'method' => 'post', 'files' => true, 'class' => 'row g-3 align-items-end']) }}
                <div class="col-md-4">
                    {{ Form::label('file', __('messages.medicine_import.file') . ':', ['class' => 'form-label required']) }}
                    <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                </div>
                <div class="col-md-3">
                    {{ Form::label('category_id', __('messages.medicine_import.default_category') . ':', ['class' => 'form-label required']) }}
                    {{ Form::select('category_id', $categories, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                </div>
                <div class="col-md-3">
                    {{ Form::label('brand_id', __('messages.medicine_import.default_brand') . ':', ['class' => 'form-label required']) }}
                    {{ Form::select('brand_id', $brands, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">{{ __('messages.medicine_import.import') }}</button></div>
                {{ Form::close() }}
            </div>
        </div>
        @if ($summary && ($summary['unknown'] || $summary['errors']))
            <div class="card">
                <div class="card-body">
                    @foreach ($summary['unknown'] as $list => $values)
                        <div class="mb-3"><b>{{ __('messages.medicine_import.unknown', ['list' => __('messages.master_list.types.' . $list)]) }}</b> {{ implode(', ', $values) }}
                            @canroute('master-lists.index') · <a href="{{ route('master-lists.index', ['type' => $list]) }}">{{ __('messages.master_list.types.' . $list) }}</a>@endcanroute
                        </div>
                    @endforeach
                    @foreach ($summary['errors'] as $error)
                        <div class="text-danger">{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
