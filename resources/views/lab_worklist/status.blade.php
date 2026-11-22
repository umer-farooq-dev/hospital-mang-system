@php($colors = ['ordered' => 'secondary', 'collected' => 'info', 'in_progress' => 'warning', 'validated' => 'primary', 'released' => 'success'])
<span class="badge bg-light-{{ $colors[$status] ?? 'secondary' }}">{{ __('messages.lab.statuses.' . $status) }}</span>
