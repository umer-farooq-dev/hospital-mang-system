<div class="d-flex align-items-center">
    <span class="fs-6 me-3">{{ $row->name }}</span>
    @if ($row->isBuiltIn())
        <span class="badge bg-light-info">{{ __('messages.access.built_in') }}</span>
    @else
        <span class="badge bg-light-success">{{ __('messages.access.custom') }}</span>
    @endif
    @if ($row->isAdmin())
        <span class="badge bg-light-warning ms-2">{{ __('messages.access.locked') }}</span>
    @endif
</div>
