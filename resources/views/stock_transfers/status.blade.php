@php
    $colors = ['requested' => 'warning', 'approved' => 'info', 'sent' => 'primary', 'received' => 'success', 'cancelled' => 'danger'];
@endphp
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }} text-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.stock_transfer.statuses.' . $status) }}</span>
