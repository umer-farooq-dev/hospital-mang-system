{{-- Back arrow: the installed app (own window) has no browser buttons. Goes to the previous page of this site, else to $fallback. --}}
<a href="{{ $fallback }}" class="app-back-button {{ $class ?? '' }}" title="{{ __('messages.common.back') }}" aria-label="{{ __('messages.common.back') }}"
    onclick="if (document.referrer.indexOf(location.origin) === 0 && history.length > 1) { event.preventDefault(); history.back(); }">
    <i class="fa-solid fa-arrow-left"></i>
</a>
