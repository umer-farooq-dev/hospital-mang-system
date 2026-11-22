@extends('layouts.app')
@section('title')
    {{ __('messages.doctor_fees.statement') }} {{ $statement->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid d-print-none">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') @include('doctor_fees.status', ['status' => $statement->status])</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                @canroute('doctor-fees.export')<a href="{{ route('doctor-fees.export', $statement->id) }}" class="btn btn-outline-primary ms-2">{{ __('messages.stock_report.export') }}</a>@endcanroute
                <a href="{{ route('doctor-fees.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap mb-7">
                    <div>
                        <div class="fs-3 fw-bold">{{ getAppName() }}</div>
                        <div class="text-muted">{{ __('messages.doctor_fees.statement') }} N° <b>{{ $statement->number }}</b></div>
                        <div class="text-muted">{{ __('messages.claims.period') }}: {{ $statement->period_from->format('d/m/Y') }} – {{ $statement->period_to->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">{{ $statement->doctor->doctorUser->full_name ?? '' }}</div>
                        <div class="text-muted">{{ __('messages.doctor_fees.pay_types.' . $statement->pay_type) }}{{ in_array($statement->pay_type, ['share'], true) ? '' : ' · ' . formatMoney($plan->rate) }}</div>
                    </div>
                </div>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.stock.date') }}</th>
                            <th>{{ __('messages.doctor_fees.kind') }}</th>
                            <th>{{ __('messages.doctor_fees.detail') }}</th>
                            <th class="text-end">{{ __('messages.doctor_fees.base') }}</th>
                            <th class="text-end">%</th>
                            <th class="text-end">{{ __('messages.doctor_fees.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statement->lines->where('kind', '!=', 'withholding') as $line)
                            <tr>
                                <td>{{ $line->line_date?->format('d/m/Y') }}</td>
                                <td>{{ __('messages.doctor_fees.kinds.' . $line->kind) }}</td>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ formatMoney($line->base_amount) }}</td>
                                <td class="text-end">{{ $line->percent !== null ? rtrim(rtrim(number_format($line->percent, 2, ',', ''), '0'), ',') : '' }}</td>
                                <td class="text-end">{{ formatMoney($line->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">{{ __('messages.doctor_fees.no_acts') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold"><td colspan="5" class="text-end">{{ __('messages.doctor_fees.gross') }}</td><td class="text-end">{{ formatMoney($statement->gross) }}</td></tr>
                        @foreach ($statement->lines->where('kind', 'withholding') as $line)
                            <tr><td colspan="4" class="text-end">{{ $line->description }}</td><td class="text-end">{{ rtrim(rtrim(number_format($line->percent, 2, ',', ''), '0'), ',') }}</td><td class="text-end text-danger">{{ formatMoney($line->amount) }}</td></tr>
                        @endforeach
                        <tr class="fw-bold fs-5"><td colspan="5" class="text-end">{{ __('messages.doctor_fees.net') }}</td><td class="text-end">{{ formatMoney($statement->net) }}</td></tr>
                    </tfoot>
                </table>
                @if ($statement->status === 'paid')
                    <div class="text-muted">{{ __('messages.doctor_fees.paid_info', ['date' => $statement->paid_on?->format('d/m/Y'), 'method' => \App\Models\MasterListItem::label('payment_method', $statement->payment_method, ''), 'reference' => $statement->payment_reference]) }}</div>
                @endif
                <div class="d-none d-print-flex justify-content-between mt-10">
                    <div>{{ __('messages.doctor_fees.signature_doctor') }}</div>
                    <div>{{ __('messages.doctor_fees.signature_hospital') }}</div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-3 d-print-none">
            @if ($statement->status === 'draft')
                @canroute('doctor-fees.update')
                    {{ Form::open(['route' => ['doctor-fees.update', $statement->id], 'method' => 'post']) }}<button class="btn btn-light-primary">{{ __('messages.doctor_fees.recompute') }}</button>{{ Form::close() }}
                @endcanroute
                @canroute('doctor-fees.approve')
                    {{ Form::open(['route' => ['doctor-fees.approve', $statement->id], 'method' => 'post']) }}<button class="btn btn-primary">{{ __('messages.doctor_fees.validate') }}</button>{{ Form::close() }}
                @endcanroute
            @endif
            @if ($statement->status === 'validated')
                @canroute('doctor-fees.pay')
                    {{ Form::open(['route' => ['doctor-fees.pay', $statement->id], 'method' => 'post', 'class' => 'd-flex gap-2 align-items-center']) }}
                    <input type="date" name="paid_on" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    {{ Form::select('payment_method', \App\Models\MasterListItem::options('payment_method'), null, ['class' => 'form-select', 'placeholder' => __('messages.claim_settlement.method')]) }}
                    <input type="text" name="payment_reference" maxlength="100" class="form-control" placeholder="{{ __('messages.claim_settlement.reference') }}">
                    <button class="btn btn-success text-nowrap">{{ __('messages.doctor_fees.mark_paid') }}</button>
                    {{ Form::close() }}
                @endcanroute
            @endif
            @if (in_array($statement->status, ['draft', 'validated'], true))
                @canroute('doctor-fees.cancel')
                    {{ Form::open(['route' => ['doctor-fees.cancel', $statement->id], 'method' => 'post']) }}<button class="btn btn-light-danger">{{ __('messages.stock_transfer.cancel') }}</button>{{ Form::close() }}
                @endcanroute
            @endif
        </div>
    </div>
@endsection
