@php
    $colors = ['draft' => 'warning', 'submitted' => 'info', 'approved' => 'success', 'cancelled' => 'danger'];
@endphp
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }} text-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.stock_count.statuses.' . $status) }}</span>
