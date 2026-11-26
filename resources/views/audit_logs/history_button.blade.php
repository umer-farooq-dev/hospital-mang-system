{{-- History of a record for the admin: @include('audit_logs.history_button', ['type' => 'receipt', 'id' => $receipt->id, 'small' => true]) --}}
@canroute('audit-logs.history')
    <a href="{{ route('audit-logs.history', [$type, $id]) }}" class="btn {{ ! empty($small) ? 'btn-sm btn-light' : 'btn-outline-secondary' }}"
        title="{{ __('messages.audit_log.history') }}">
        <i class="fas fa-history"></i>@if (empty($small)) <span class="ms-1">{{ __('messages.audit_log.history') }}</span>@endif
    </a>
@endcanroute
