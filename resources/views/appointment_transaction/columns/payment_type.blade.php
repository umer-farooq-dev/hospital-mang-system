@if ($row->appointment->payment_type == \App\Models\Appointment::TYPE_STRIPE)
    <span class="badge bg-light-primary">{{ __('messages.bill.stripe') }}</span>
@elseif($row->appointment->payment_type == \App\Models\Appointment::TYPE_RAZORPAY)
    <span class="badge bg-light-success">{{ __('messages.razorpay') }}</span>
@elseif ($row->appointment->payment_type == \App\Models\Appointment::TYPE_PAYPAL)
    <span class="badge bg-light-primary">{{ __('messages.paypal') }}</span>
@elseif(\App\Models\PaymentMethod::isOffline($row->appointment->payment_type))
    <span class="badge bg-light-{{ $row->appointment->payment_type == 2 ? 'warning' : 'info' }}">{{ \App\Models\PaymentMethod::label($row->appointment->payment_type) }}</span>
@elseif($row->appointment->payment_type == \App\Models\Appointment::OTHER)
    <span class="badge bg-light-info">{{ __('messages.other') }}</span>
@elseif($row->appointment->payment_type == \App\Models\Appointment::FLUTTERWAVE)
    <span class="badge bg-light-success">{{ __('messages.flutterwave') }}</span>
@elseif($row->appointment->payment_type == \App\Models\Appointment::PHONEPE)
    <span class="badge bg-light-warning">{{ __('messages.phonepe') }}</span>
@elseif($row->appointment->payment_type == \App\Models\Appointment::PAYSTACK)
    <span class="badge bg-light-warning">{{ __('messages.paystack') }}</span>
@else
    {{ __('messages.common.n/a') }}
@endif
