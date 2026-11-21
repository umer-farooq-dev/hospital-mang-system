<div class="border rounded p-4 bg-light">
    <div class="row text-center g-3">
        <div class="col-6 col-md-3">
            <div class="text-muted fs-7">{{ __('messages.split.gross') }}</div>
            <div class="fs-5 fw-bold">{{ formatMoney($split['gross']) }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted fs-7">{{ __('messages.split.discount') }}{{ $split['discount_percent'] > 0 ? ' (' . formatPercent($split['discount_percent']) . ')' : '' }}</div>
            <div class="fs-5 fw-bold">{{ formatMoney($split['discount_amount']) }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted fs-7">{{ __('messages.split.payer_share') }}{{ ! empty($split['coverage_percent']) ? ' (' . formatPercent($split['coverage_percent']) . ')' : '' }}</div>
            <div class="fs-5 fw-bold">{{ formatMoney($split['payer_amount']) }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted fs-7">{{ __('messages.split.patient_share') }}</div>
            <div class="fs-4 fw-bolder text-primary">{{ formatMoney($split['patient_amount']) }}</div>
        </div>
    </div>
    @foreach ($split['warnings'] as $warning)
        <div class="alert alert-warning py-2 px-3 mt-3 mb-0 fs-7">{{ __('messages.split.warnings.' . $warning) }}</div>
    @endforeach
</div>
