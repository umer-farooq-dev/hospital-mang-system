@extends('layouts.app')
@section('title')
    {{ __('messages.audit_log.history_title') }} · {{ __('messages.audit_log.history_types.'.$type) }} #{{ $id }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $badges = ['created' => 'success', 'updated' => 'warning', 'deleted' => 'danger'];
    @endphp
    <div class="container-fluid">
        @if ($logs->isEmpty())
            <div class="card"><div class="card-body text-muted text-center py-10">{{ __('messages.audit_log.history_empty') }}</div></div>
        @endif
        @foreach ($logs as $log)
            <div class="card mb-5">
                <div class="card-header min-h-50px align-items-center">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="badge badge-light-{{ $badges[$log->event] ?? 'primary' }}">{{ __('messages.audit_log.history_events.'.$log->event) }}</span>
                        <span class="fw-bold">{{ $log->record_name }} #{{ $log->auditable_id }}</span>
                    </div>
                    <div class="text-muted fs-7">
                        <i class="fas fa-user me-1"></i>{{ $log->user->full_name ?? '—' }}
                        <i class="fas fa-clock ms-4 me-1"></i>{{ $log->created_at->format('d/m/Y H:i:s') }}
                        @if ($log->ip_address)<span class="ms-4">IP {{ $log->ip_address }}</span>@endif
                    </div>
                </div>
                @php($changes = $log->changes())
                @if ($changes)
                    <div class="card-body py-3">
                        <table class="table table-row-dashed align-middle fs-6 mb-0">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th class="w-25">{{ __('messages.audit_log.field') }}</th>
                                    @if ($log->event !== 'created')<th>{{ __('messages.audit_log.before') }}</th>@endif
                                    @if ($log->event !== 'deleted')<th>{{ __('messages.audit_log.after') }}</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changes as $field => [$before, $after])
                                    <tr>
                                        <td class="fw-bold">{{ \App\Models\AuditLog::fieldLabel($field) }}</td>
                                        @if ($log->event !== 'created')<td class="{{ $log->event === 'updated' ? 'text-danger' : '' }}">{{ \App\Models\AuditLog::displayValue($field, $before) }}</td>@endif
                                        @if ($log->event !== 'deleted')<td class="{{ $log->event === 'updated' ? 'text-success fw-bold' : '' }}">{{ \App\Models\AuditLog::displayValue($field, $after) }}</td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
