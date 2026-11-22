@extends('layouts.app')
@section('title')
    {{ __('messages.stock.suppliers') }}
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
        $canEdit = canAccessRoute('suppliers.update');
        $fields = ['name' => 150, 'contact_person' => 150, 'phone' => 50, 'email' => 150, 'address' => 255, 'tax_number' => 50];
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'suppliers.update', 'method' => 'post']) }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                @foreach ($fields as $field => $max)
                                    <th>{{ __('messages.stock.' . ($field === 'name' ? 'supplier' : $field)) }}</th>
                                @endforeach
                                <th>{{ __('messages.common.active') }}</th>
                            </tr>
                        </thead>
                        @foreach ($suppliers->push(null) as $supplier)
                            @continue(! $supplier && ! $canEdit)
                            @php
                                $f = $supplier ? 'suppliers[' . $supplier->id . ']' : 'new';
                            @endphp
                            <tr class="{{ $supplier ? '' : 'bg-light-primary' }}">
                                @foreach ($fields as $field => $max)
                                    <td>{{ Form::text($f . '[' . $field . ']', $supplier?->{$field}, ['class' => 'form-control form-control-sm', 'maxlength' => $max]
                                        + ($field === 'name' && $supplier ? ['required' => 'required'] : [])
                                        + ($field === 'name' && ! $supplier ? ['placeholder' => __('messages.stock.new_supplier')] : [])
                                        + ($canEdit ? [] : ['disabled' => 'disabled'])) }}</td>
                                @endforeach
                                <td>@if ($supplier)<input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $supplier->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>@endif</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @if ($canEdit)
                    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
