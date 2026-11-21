@php
    // Badge colour follows the charge type code, as before (1 primary, 2 info, 3 success, 4 danger, 5 warning...).
    $chargeTypeColours = ['primary', 'info', 'success', 'danger', 'warning'];
@endphp
<span class="badge bg-light-{{ $chargeTypeColours[max((int) $row->charge_type - 1, 0) % 5] }}">
    {{ \App\Models\MasterListItem::label('charge_type', $row->charge_type, __('messages.common.n/a')) }}
</span>
