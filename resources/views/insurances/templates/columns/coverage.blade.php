<div>
    {{ isset($row->coverage_percent) ? formatPercent($row->coverage_percent) : __('messages.common.n/a') }}
    @if (! empty($row->discount))
        <div class="text-muted fs-7">{{ __('messages.insurance.discount') }} {{ formatPercent($row->discount) }}</div>
    @endif
</div>
