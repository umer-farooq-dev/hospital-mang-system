@php
    $colors = ['issued' => 'warning', 'partial' => 'info', 'paid' => 'success', 'closed' => 'secondary', 'cancelled' => 'danger',
        'pending' => 'warning', 'rejected' => 'danger', 'resubmit' => 'info', 'written_off' => 'secondary', 'to_patient' => 'primary'];
@endphp
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }} text-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.claims.statuses.' . $status) }}</span>
