@extends('settings.edit')
@section('title')
    {{ __('messages.discharge.inpatient') }}
@endsection
@section('section')
    @php($values = \App\Models\Setting::whereIn('key', ['discharge_block_unpaid', 'discharge_require_checklist', 'discharge_summary_template'])->pluck('value', 'key'))
    {{ Form::open(['route' => 'settings.inpatient.update', 'method' => 'post']) }}
    <div class="row">
        <div class="col-md-12 mb-3"><h5>{{ __('messages.discharge.discharge') }}</h5></div>
        <div class="col-md-6 mb-5">
            <label class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="discharge_block_unpaid" value="1" {{ ($values['discharge_block_unpaid'] ?? '0') === '1' ? 'checked' : '' }}>
                <span class="form-check-label">{{ __('messages.discharge.block_unpaid') }}</span>
            </label>
            <div class="form-text">{{ __('messages.discharge.block_unpaid_hint') }}</div>
        </div>
        <div class="col-md-6 mb-5">
            <label class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="discharge_require_checklist" value="1" {{ ($values['discharge_require_checklist'] ?? '0') === '1' ? 'checked' : '' }}>
                <span class="form-check-label">{{ __('messages.discharge.require_checklist') }}</span>
            </label>
            <div class="form-text">{{ __('messages.discharge.require_checklist_hint') }}</div>
        </div>
        <div class="col-md-12 mb-5">
            {{ Form::label('discharge_summary_template', __('messages.discharge.template') . ':', ['class' => 'form-label']) }}
            {{ Form::textarea('discharge_summary_template', $values['discharge_summary_template'] ?? '', ['class' => 'form-control font-monospace', 'rows' => 14, 'maxlength' => 20000]) }}
            <div class="form-text">{{ __('messages.discharge.placeholders') }}:
                @foreach (\App\Models\IpdPatientDepartment::SUMMARY_PLACEHOLDERS as $placeholder)
                    <code>{{ '{' . $placeholder . '}' }}</code>
                @endforeach
            </div>
        </div>
        @canroute('master-lists.index')
        <div class="col-md-12 mb-5">
            @foreach (['discharge_check', 'discharge_outcome'] as $list)
                <a href="{{ route('master-lists.index', ['type' => $list]) }}" class="text-decoration-none me-5">
                    <i class="fas fa-list me-1"></i>{{ __('messages.master_list.types.' . $list) }}
                </a>
            @endforeach
        </div>
        @endcanroute
    </div>
    <div class="d-flex justify-content-end">
        {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-3']) }}
    </div>
    {{ Form::close() }}
@endsection
