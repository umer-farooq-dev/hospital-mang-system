@extends('layouts.app')
@section('title')
    {{ __('messages.invoice.invoice_details')}}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="text-end mt-4 mt-md-0">
                @if ($invoice->isProforma() && ! $invoice->converted_invoice_id && canAccessRoute('invoices.convert'))
                    {{ Form::open(['route' => ['invoices.convert', $invoice->id], 'method' => 'post', 'class' => 'd-inline']) }}
                    <button type="submit" class="btn btn-success me-2" {{ $invoice->isExpired() ? 'disabled' : '' }}>{{ __('messages.proforma.convert') }}</button>
                    {{ Form::close() }}
                @endif
                @unless ($invoice->converted_invoice_id)
                <a class="btn btn-primary edit-btn"
                   href="{{ route('invoices.edit', ['invoice' => $invoice->id]) }}">{{ __('messages.common.edit') }}</a>
                @endunless
                @include('audit_logs.history_button', ['type' => 'invoice', 'id' => $invoice->id])
                <a href="{{ url()->previous() }}"
                   class="btn btn-outline-primary {{getCurrentLoginUserLanguageName()=='ar' ? 'me-2' : 'ms-2'}}">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @if ($invoice->isProforma())
            <div class="alert alert-important {{ $invoice->isExpired() && ! $invoice->converted_invoice_id ? 'alert-warning' : 'alert-info' }}">
                {{ __('messages.proforma.types.proforma') }} · {{ __('messages.proforma.valid_until') }} {{ $invoice->valid_until ? \Illuminate\Support\Carbon::parse($invoice->valid_until)->format('d/m/Y') : '—' }}
                @if ($invoice->insurance) · {{ __('messages.claims.payer') }}: {{ $invoice->insurance->name }}@endif
                @if ($invoice->priceList) · {{ __('messages.proforma.price_list') }}: {{ $invoice->priceList->name }}@endif
                @if ($invoice->converted_invoice_id)
                    · <a href="{{ route('invoices.show', $invoice->converted_invoice_id) }}">{{ __('messages.proforma.converted_to', ['number' => \App\Models\Invoice::whereKey($invoice->converted_invoice_id)->value('invoice_id')]) }}</a>
                @elseif ($invoice->isExpired())
                    · {{ __('messages.proforma.expired') }}
                @endif
            </div>
        @endif
        <div class="d-flex flex-column">
            <div class="card">
                <div class="card-body">
                        @include('invoices.show_fields')
                </div>
            </div>
        </div>
    </div>
@endsection
