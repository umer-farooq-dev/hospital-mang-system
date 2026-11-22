@extends('settings.edit')
@section('title')
    {{ __('messages.discharge.inpatient') }}
@endsection
@section('section')
    @php($values = \App\Models\Setting::whereIn('key', ['discharge_block_unpaid', 'discharge_require_checklist', 'discharge_summary_template', 'mar_dose_times'])->pluck('value', 'key'))
    @php($doseTimes = json_decode($values['mar_dose_times'] ?? '{}', true) ?: [])
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
        <div class="col-md-12 mb-3 mt-3"><h5>{{ __('messages.nursing.mar_times') }}</h5><div class="form-text mb-3">{{ __('messages.nursing.mar_times_hint') }}</div></div>
        @foreach (range(1, 6) as $perDay)
            <div class="col-md-4 mb-5">
                {{ Form::label('mar_dose_times[' . $perDay . ']', trans_choice('messages.nursing.doses_per_day', $perDay, ['count' => $perDay]) . ':', ['class' => 'form-label']) }}
                {{ Form::text('mar_dose_times[' . $perDay . ']', $doseTimes[$perDay] ?? '', ['class' => 'form-control', 'placeholder' => implode(',', \App\Models\MedicationAdministration::doseTimes($perDay, []))]) }}
            </div>
        @endforeach
        @canroute('master-lists.index')
        <div class="col-md-12 mb-5">
            @canroute('icd10.index')
                <a href="{{ route('icd10.index') }}" class="text-decoration-none me-5"><i class="fas fa-book-medical me-1"></i>{{ __('messages.icd10.list') }}</a>
            @endcanroute
            @foreach (['discharge_check', 'discharge_outcome', 'nursing_shift', 'intake_type', 'output_type', 'mar_status', 'dose_interval', 'dose_duration', 'diagnosis_status'] as $list)
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

    <hr class="my-8">
    <h5 class="mb-2">{{ __('messages.nursing.vital_types') }}</h5>
    <div class="form-text mb-4">{{ __('messages.nursing.vital_types_hint') }}</div>
    {{ Form::open(['route' => 'settings.vital-types.update', 'method' => 'post']) }}
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed">
            <thead>
                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                    <th>{{ __('messages.nursing.vital') }}</th><th style="width:120px">{{ __('messages.nursing.unit') }}</th>
                    <th style="width:120px">{{ __('messages.nursing.normal_min') }}</th><th style="width:120px">{{ __('messages.nursing.normal_max') }}</th>
                    <th style="width:100px">{{ __('messages.master_list.sort_order') }}</th><th class="text-center">{{ __('messages.common.active') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Models\VitalType::orderBy('sort_order')->orderBy('id')->get()->push(null) as $vital)
                    @php($f = $vital ? 'types[' . $vital->id . ']' : 'new')
                    <tr class="{{ $vital ? '' : 'bg-light' }}">
                        <td><input type="text" class="form-control form-control-sm" name="{{ $f }}[name]" value="{{ $vital?->label }}" maxlength="100" placeholder="{{ $vital ? '' : __('messages.common.add') }}" {{ $vital ? 'required' : '' }}></td>
                        <td><input type="text" class="form-control form-control-sm" name="{{ $f }}[unit]" value="{{ $vital?->unit }}" maxlength="30"></td>
                        <td><input type="number" step="any" class="form-control form-control-sm" name="{{ $f }}[normal_min]" value="{{ $vital && $vital->normal_min !== null ? $vital->normal_min + 0 : '' }}"></td>
                        <td><input type="number" step="any" class="form-control form-control-sm" name="{{ $f }}[normal_max]" value="{{ $vital && $vital->normal_max !== null ? $vital->normal_max + 0 : '' }}"></td>
                        <td><input type="number" class="form-control form-control-sm" name="{{ $f }}[sort_order]" value="{{ $vital?->sort_order }}" min="0" max="9999"></td>
                        <td class="text-center">@if ($vital)<input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $vital->is_active ? 'checked' : '' }}>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-3']) }}</div>
    {{ Form::close() }}
@endsection
