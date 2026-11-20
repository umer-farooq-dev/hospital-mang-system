@if ($row->user)
    <div class="d-flex flex-column">
        <span class="mb-1 fs-6">{{ $row->user->full_name }}</span>
        <span class="fs-6 text-gray-600">{{ $row->user->email }}</span>
    </div>
@else
    <span class="text-gray-600">{{ __('messages.audit_log.system') }}</span>
@endif
