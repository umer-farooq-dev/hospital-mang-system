@extends('layouts.app')
@section('title')
    {{ __('messages.price_list.price_lists') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('price-lists.tariff')
            <a href="{{ route('price-lists.tariff') }}" class="btn btn-primary"><i class="fas fa-table me-1"></i>{{ __('messages.price_list.tariff') }}</a>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('price-lists.update');
        $dayNames = collect(range(1, 7))->mapWithKeys(fn ($d) => [$d => \Carbon\Carbon::now()->startOfWeek()->addDays($d - 1)->translatedFormat('D')]);
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            @include('layouts.errors')

            <div class="card mb-7">
                <div class="card-header border-0 pt-6">
                    <div>
                        <h3 class="card-title fw-bold mb-1">{{ __('messages.price_list.price_lists') }}</h3>
                        <div class="text-muted fs-7">{{ __('messages.price_list.lists_hint') }}</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    {{ Form::open(['route' => 'price-lists.update', 'method' => 'post']) }}
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th class="text-center">{{ __('messages.price_list.default') }}</th>
                                    <th>{{ __('messages.price_list.name') }}</th>
                                    <th style="width: 180px">{{ __('messages.price_list.adjustment_percent') }}</th>
                                    <th class="text-center">{{ __('messages.common.active') }}</th>
                                    <th class="text-center">{{ __('messages.common.delete') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lists as $list)
                                    <tr>
                                        <td class="text-center">
                                            <input type="radio" class="form-check-input" name="default_id" value="{{ $list->id }}" {{ $list->is_default ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="lists[{{ $list->id }}][name]" value="{{ old('lists.' . $list->id . '.name', $list->label) }}" maxlength="100" required {{ $canEdit ? '' : 'disabled' }}>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="lists[{{ $list->id }}][adjustment_percent]" value="{{ old('lists.' . $list->id . '.adjustment_percent', $list->adjustment_percent + 0) }}" step="0.01" min="-100" max="1000" required {{ $canEdit ? '' : 'disabled' }}>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" name="lists[{{ $list->id }}][is_active]" value="1" {{ $list->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                        </td>
                                        <td class="text-center">
                                            @if ($list->isInUse())
                                                <span class="text-muted fs-7" title="{{ __('messages.price_list.in_use_hint') }}">{{ __('messages.price_list.used') }}</span>
                                            @else
                                                <input type="checkbox" class="form-check-input border-danger" name="lists[{{ $list->id }}][delete]" value="1" {{ $canEdit ? '' : 'disabled' }}>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($canEdit)
                                    <tr class="bg-light">
                                        <td class="text-center text-muted fs-7">{{ __('messages.common.add') }}</td>
                                        <td><input type="text" class="form-control" name="new[name]" value="{{ old('new.name') }}" maxlength="100" placeholder="{{ __('messages.price_list.new_list') }}"></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="new[adjustment_percent]" value="{{ old('new.adjustment_percent', 0) }}" step="0.01" min="-100" max="1000">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if ($canEdit)
                        <div class="d-flex justify-content-end">
                            {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                        </div>
                    @endif
                    {{ Form::close() }}
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div>
                        <h3 class="card-title fw-bold mb-1">{{ __('messages.price_list.periods') }}</h3>
                        <div class="text-muted fs-7">{{ __('messages.price_list.periods_hint') }}</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    {{ Form::open(['route' => 'price-lists.periods.update', 'method' => 'post']) }}
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th>{{ __('messages.price_list.name') }}</th>
                                    <th>{{ __('messages.price_list.days') }}</th>
                                    <th style="width: 130px">{{ __('messages.price_list.start_time') }}</th>
                                    <th style="width: 130px">{{ __('messages.price_list.end_time') }}</th>
                                    <th style="width: 160px">{{ __('messages.price_list.surcharge_percent') }}</th>
                                    <th class="text-center">{{ __('messages.common.active') }}</th>
                                    <th class="text-center">{{ __('messages.common.delete') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($periods->push(null) as $period)
                                    @continue(! $period && ! $canEdit)
                                    @php($field = $period ? 'periods[' . $period->id . ']' : 'new_period')
                                    <tr class="{{ $period ? '' : 'bg-light' }}">
                                        <td><input type="text" class="form-control" name="{{ $field }}[name]" value="{{ $period?->label }}" maxlength="100" {{ $period ? 'required' : '' }} placeholder="{{ $period ? '' : __('messages.price_list.new_period') }}" {{ $canEdit ? '' : 'disabled' }}></td>
                                        <td class="text-nowrap">
                                            @foreach ($dayNames as $d => $dayName)
                                                <label class="form-check form-check-inline form-check-sm me-2">
                                                    <input type="checkbox" class="form-check-input" name="{{ $field }}[days][]" value="{{ $d }}" {{ $period && in_array($d, $period->dayList()) ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                                    <span class="form-check-label fs-7">{{ $dayName }}</span>
                                                </label>
                                            @endforeach
                                        </td>
                                        <td><input type="time" class="form-control" name="{{ $field }}[start_time]" value="{{ $period?->start_time ? substr($period->start_time, 0, 5) : '' }}" {{ $canEdit ? '' : 'disabled' }}></td>
                                        <td><input type="time" class="form-control" name="{{ $field }}[end_time]" value="{{ $period?->end_time ? substr($period->end_time, 0, 5) : '' }}" {{ $canEdit ? '' : 'disabled' }}></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="{{ $field }}[surcharge_percent]" value="{{ $period ? $period->surcharge_percent + 0 : 0 }}" step="0.01" min="0" max="1000" {{ $canEdit ? '' : 'disabled' }}>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if ($period)
                                                <input type="checkbox" class="form-check-input" name="{{ $field }}[is_active]" value="1" {{ $period->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($period)
                                                <input type="checkbox" class="form-check-input border-danger" name="{{ $field }}[delete]" value="1" {{ $canEdit ? '' : 'disabled' }}>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($canEdit)
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted fs-7">{{ __('messages.price_list.periods_time_hint') }}</div>
                            {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                        </div>
                    @endif
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
