@if ($row->payment_mode == \App\Models\IpdPayment::PAYMENT_MODES_STRIPE)
    <span class="badge bg-light-primary">{{ __('messages.bill.stripe') }}</span>
@elseif($row->payment_mode == \App\Models\IpdPayment::PAYMENT_MODES_RAZORPAY)
    <span class="badge bg-light-success">{{ __('messages.razorpay') }}</span>
@elseif($row->payment_mode == \App\Models\IpdPayment::PAYMENT_MODES_FLUTTERWAVE)
    <span class="badge bg-light-primary ">{{ __('messages.flutterwave') }}</span>
@elseif($row->payment_mode == \App\Models\IpdPayment::PAYMENT_MODES_PHONEPE)
    <span class="badge bg-light-success">{{ __('messages.phonepe') }}</span>
@elseif($row->payment_mode == \App\Models\IpdPayment::PAYMENT_MODES_PAYSTACK)
    <span class="badge bg-light-success">{{ __('messages.paystack') }}</span>
@elseif(\App\Models\PaymentMethod::isOffline($row->payment_mode))
    <span class="badge bg-light-{{ $row->payment_mode == 1 ? 'info' : 'warning' }}">{{ \App\Models\PaymentMethod::label($row->payment_mode) }}</span>
@endif
