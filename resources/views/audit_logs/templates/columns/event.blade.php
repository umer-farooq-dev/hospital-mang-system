@php
    $eventBadge = ['created' => 'bg-light-success', 'updated' => 'bg-light-warning', 'deleted' => 'bg-light-danger'];
@endphp
<div class="badge {{ $eventBadge[$row->event] ?? 'bg-light-info' }}">
    {{ __('messages.audit_log.'.$row->event) }}
</div>
