@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.new_entry') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('accounting.entries.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'accounting.entries.store', 'method' => 'post', 'id' => 'entryForm']) }}
                <div class="row">
                    <div class="col-md-3 mb-5">{{ Form::label('journal_id', __('messages.accounting.journal') . ':', ['class' => 'form-label required']) }}{{ Form::select('journal_id', $journals, old('journal_id'), ['class' => 'form-select', 'required']) }}</div>
                    <div class="col-md-2 mb-5">{{ Form::label('entry_date', __('messages.stock.date') . ':', ['class' => 'form-label required']) }}{{ Form::date('entry_date', old('entry_date', now()->toDateString()), ['class' => 'form-control', 'required']) }}</div>
                    <div class="col-md-2 mb-5">{{ Form::label('reference', __('messages.accounting.reference') . ':', ['class' => 'form-label']) }}{{ Form::text('reference', old('reference'), ['class' => 'form-control', 'maxlength' => 100]) }}</div>
                    <div class="col-md-5 mb-5">{{ Form::label('label', __('messages.accounting.label') . ':', ['class' => 'form-label required']) }}{{ Form::text('label', old('label'), ['class' => 'form-control', 'maxlength' => 255, 'required']) }}</div>
                </div>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.accounting.account') }}</th><th>{{ __('messages.accounting.label') }}</th><th class="w-150px">{{ __('messages.accounting.debit') }}</th><th class="w-150px">{{ __('messages.accounting.credit') }}</th></tr></thead>
                    <tbody>
                        @for ($i = 0; $i < 12; $i++)
                            <tr class="entry-line {{ $i >= 2 && ! old('lines.' . $i . '.account_id') ? 'd-none' : '' }}">
                                <td>{{ Form::select('lines[' . $i . '][account_id]', $accounts, old('lines.' . $i . '.account_id'), ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.common.choose')]) }}</td>
                                <td>{{ Form::text('lines[' . $i . '][label]', old('lines.' . $i . '.label'), ['class' => 'form-control form-control-sm', 'maxlength' => 255]) }}</td>
                                <td>{{ Form::number('lines[' . $i . '][debit]', old('lines.' . $i . '.debit'), ['class' => 'form-control form-control-sm text-end entry-debit', 'min' => 0, 'step' => '0.01']) }}</td>
                                <td>{{ Form::number('lines[' . $i . '][credit]', old('lines.' . $i . '.credit'), ['class' => 'form-control form-control-sm text-end entry-credit', 'min' => 0, 'step' => '0.01']) }}</td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">{{ __('messages.claims.total') }}</td><td class="text-end" id="sumDebit">0</td><td class="text-end" id="sumCredit">0</td></tr></tfoot>
                </table>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-light-primary" onclick="var r=document.querySelector('.entry-line.d-none'); if (r) { r.classList.remove('d-none'); }">{{ __('messages.stock_transfer.add_line') }}</button>
                    {{ Form::submit(__('messages.accounting.post'), ['class' => 'btn btn-primary']) }}
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
    <script>
        document.getElementById('entryForm').addEventListener('input', function () {
            var sum = function (selector) { return Array.prototype.reduce.call(document.querySelectorAll(selector), function (t, el) { return t + (parseFloat(el.value) || 0); }, 0).toFixed(2); };
            document.getElementById('sumDebit').textContent = sum('.entry-debit');
            document.getElementById('sumCredit').textContent = sum('.entry-credit');
        });
    </script>
@endsection
