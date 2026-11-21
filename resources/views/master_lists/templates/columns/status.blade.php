@if ($row->is_active)
    <span class="badge bg-light-success">{{ __('messages.master_list.active') }}</span>
@else
    <span class="badge bg-light-danger">{{ __('messages.master_list.inactive') }}</span>
@endif
