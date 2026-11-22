@php
    $colors = ['draft' => 'warning', 'validated' => 'info', 'paid' => 'success', 'cancelled' => 'danger'];
@endphp
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }} text-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.doctor_fees.statuses.' . $status) }}</span>
