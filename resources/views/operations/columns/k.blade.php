@if ($row->k_surgeon !== null || $row->k_assistant !== null || $row->k_anaesthetist !== null)
    <span class="fs-7" title="{{ __('messages.ot.k_surgeon') }} / {{ __('messages.ot.k_assistant') }} / {{ __('messages.ot.k_anaesthetist') }}">
        K {{ $row->k_surgeon + 0 }} / {{ $row->k_assistant + 0 }} / {{ $row->k_anaesthetist + 0 }}
    </span>
@else
    <span class="text-muted">{{ __('messages.common.n/a') }}</span>
@endif
