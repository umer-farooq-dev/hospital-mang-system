@php
    $colors = ['ordered' => 'warning', 'partial' => 'info', 'received' => 'success', 'closed' => 'secondary', 'cancelled' => 'danger'];
@endphp
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }} text-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.purchase_order.statuses.' . $status) }}</span>
