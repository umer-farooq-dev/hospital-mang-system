@extends('layouts.app')
@section('title')
    {{ __('messages.stock_count.count') }} {{ $count->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') @include('stock_counts.status', ['status' => $count->status])</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_count.print_sheet') }}</button>
                <a href="{{ route('stock-counts.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php
        // A blind count hides what the system expects until the sheet is submitted.
        $hideSystem = $count->blind && $count->status === 'draft';
        $canApprove = $count->status === 'submitted' && canAccessRoute('stock-counts.approve');
        $varianceValue = $lines->sum(fn ($l) => ($l->variance() ?? 0) * $l->unit_cost);
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-5 mb-5">
                    <div><span class="text-muted">{{ __('messages.stock.store') }}:</span> <b>{{ $count->store->name ?? '' }}</b></div>
                    <div><span class="text-muted">{{ __('messages.stock.date') }}:</span> {{ $count->created_at->translatedFormat('d/m/Y H:i') }} · {{ $count->stepUser('created')->first()->full_name ?? '' }}</div>
                    @foreach (['submitted', 'approved', 'cancelled'] as $step)
                        @if ($count->{$step . '_at'})
                            <div><span class="text-muted">{{ __('messages.stock_count.statuses.' . $step) }}:</span> {{ $count->{$step . '_at'}->translatedFormat('d/m/Y H:i') }} · {{ $count->stepUser($step)->first()->full_name ?? '' }}</div>
                        @endif
                    @endforeach
                    @if ($count->note)<div><span class="text-muted">{{ __('messages.stock_transfer.note') }}:</span> {{ $count->note }}</div>@endif
                </div>
                @if ($canCount)
                    <div class="form-text mb-3">{{ __('messages.stock_count.count_hint') }}</div>
                    {{ Form::open(['route' => ['stock-counts.update', $count->id], 'method' => 'post', 'id' => 'countSheet']) }}
                @endif
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.medicine.medicine') }}</th>
                                <th>{{ __('messages.stock.batch') }}</th>
                                <th>{{ __('messages.stock.expiry') }}</th>
                                @unless ($hideSystem)<th class="text-end">{{ __('messages.stock_count.expected') }}</th>@endunless
                                <th class="text-end w-150px">{{ __('messages.stock_count.counted') }}</th>
                                @unless ($hideSystem)
                                    <th class="text-end">{{ __('messages.stock_count.variance') }}</th>
                                    <th class="text-end">{{ __('messages.stock_count.variance_value') }}</th>
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $line)
                                @php
                                    $variance = $line->variance();
                                @endphp
                                <tr>
                                    <td>{{ $line->stockable->name ?? '' }} @if (! $line->stock_batch_id)<span class="badge bg-light-primary text-primary ms-1">{{ __('messages.stock_count.found') }}</span>@endif</td>
                                    <td>{{ $line->batch_no ?? '—' }}</td>
                                    <td>{{ $line->expiry_date ? \Illuminate\Support\Carbon::parse($line->expiry_date)->translatedFormat('d/m/Y') : '—' }}</td>
                                    @unless ($hideSystem)<td class="text-end">{{ $line->system_quantity }}</td>@endunless
                                    <td class="text-end">
                                        @if ($canCount)
                                            <input type="number" name="counted[{{ $line->id }}]" value="{{ $line->counted_quantity }}" min="0" class="form-control form-control-sm text-end">
                                        @else
                                            {{ $line->counted_quantity ?? '—' }}
                                        @endif
                                    </td>
                                    @unless ($hideSystem)
                                        <td class="text-end fw-bold {{ $variance < 0 ? 'text-danger' : ($variance > 0 ? 'text-success' : '') }}">{{ $variance === null ? '—' : ($variance > 0 ? '+' : '') . $variance }}</td>
                                        <td class="text-end">{{ $variance ? formatMoney($variance * $line->unit_cost) : '' }}</td>
                                    @endunless
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">{{ __('messages.stock.no_batches') }}</td></tr>
                            @endforelse
                        </tbody>
                        @unless ($hideSystem)
                            <tfoot><tr class="fw-bold"><td colspan="6" class="text-end">{{ __('messages.stock_count.total_variance') }}</td><td class="text-end {{ $varianceValue < 0 ? 'text-danger' : '' }}">{{ formatMoney($varianceValue) }}</td></tr></tfoot>
                        @endunless
                    </table>
                </div>
                @if ($canCount)
                    <h6 class="mt-5">{{ __('messages.stock_count.found_title') }}</h6>
                    <div class="form-text mb-3">{{ __('messages.stock_count.found_hint') }}</div>
                    <table class="table table-row-dashed align-middle">
                        @for ($i = 0; $i < $foundRows; $i++)
                            <tr>
                                <td>{{ Form::select('found[' . $i . '][medicine]', $medicines, null, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.medicine_bills.select_medicine')]) }}</td>
                                <td>{{ Form::text('found[' . $i . '][batch_no]', null, ['class' => 'form-control form-control-sm', 'maxlength' => 100, 'placeholder' => __('messages.stock.batch')]) }}</td>
                                <td>{{ Form::date('found[' . $i . '][expiry_date]', null, ['class' => 'form-control form-control-sm']) }}</td>
                                <td class="w-150px">{{ Form::number('found[' . $i . '][quantity]', null, ['class' => 'form-control form-control-sm', 'min' => 0, 'placeholder' => __('messages.stock.quantity')]) }}</td>
                            </tr>
                        @endfor
                    </table>
                    {{ Form::close() }}
                @endif
                <div class="d-flex justify-content-end gap-3">
                    @if ($canCount)
                        <button type="submit" form="countSheet" class="btn btn-light-primary">{{ __('messages.stock_count.save_draft') }}</button>
                        <button type="submit" form="countSheet" name="submit" value="1" class="btn btn-primary">{{ __('messages.stock_count.submit') }}</button>
                    @endif
                    @if ($canApprove)
                        {{ Form::open(['route' => ['stock-counts.approve', $count->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-success">{{ __('messages.stock_count.approve') }}</button>
                        {{ Form::close() }}
                    @endif
                    @if (in_array($count->status, ['draft', 'submitted'], true) && canAccessRoute('stock-counts.cancel'))
                        {{ Form::open(['route' => ['stock-counts.cancel', $count->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-light-danger">{{ __('messages.stock_transfer.cancel') }}</button>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
