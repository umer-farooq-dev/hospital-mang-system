<div id="patientInsuranceModal" class="modal fade" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="patientInsuranceModalTitle" data-new="{{ __('messages.patient_insurance.new_policy') }}"
                    data-edit="{{ __('messages.patient_insurance.edit_policy') }}">{{ __('messages.patient_insurance.new_policy') }}</h2>
                <button type="button" aria-label="Close" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            {{ Form::open(['id' => 'patientInsuranceForm', 'files' => true, 'data-store-url' => route('patient-insurances.store', $data->id)]) }}
            <div class="modal-body">
                <div class="alert alert-danger d-none hide" id="patientInsuranceErrorsBox"></div>
                <div class="row">
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('insurance_id', __('messages.patient_insurance.insurance_id') . ':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        {{ Form::select('insurance_id', $payers, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.patient_insurance.select_payer'), 'data-control' => 'select2', 'data-dropdown-parent' => '#patientInsuranceModal']) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('plan_name', __('messages.patient_insurance.plan_name') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('plan_name', null, ['class' => 'form-control', 'maxlength' => 160]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('group_name', __('messages.patient_insurance.group_name') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('group_name', null, ['class' => 'form-control', 'maxlength' => 160, 'placeholder' => __('messages.patient_insurance.group_hint')]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('policy_number', __('messages.patient_insurance.policy_number') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('policy_number', null, ['class' => 'form-control', 'maxlength' => 100]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('member_number', __('messages.patient_insurance.member_number') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('member_number', null, ['class' => 'form-control', 'maxlength' => 100]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('valid_from', __('messages.patient_insurance.valid_from') . ':', ['class' => 'form-label']) }}
                        {{ Form::date('valid_from', null, ['class' => 'form-control']) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('valid_to', __('messages.patient_insurance.valid_to') . ':', ['class' => 'form-label']) }}
                        {{ Form::date('valid_to', null, ['class' => 'form-control']) }}
                    </div>
                    <div class="form-group col-sm-4 mb-5">
                        {{ Form::label('coverage_percent', __('messages.patient_insurance.coverage_percent') . ' (%):', ['class' => 'form-label']) }}
                        {{ Form::number('coverage_percent', null, ['class' => 'form-control', 'min' => 0, 'max' => 100, 'step' => '.01']) }}
                        <div class="form-text">{{ __('messages.patient_insurance.coverage_hint') }}</div>
                    </div>
                    <div class="form-group col-sm-4 mb-5">
                        {{ Form::label('annual_limit', __('messages.patient_insurance.annual_limit') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('annual_limit', null, ['class' => 'form-control price-input']) }}
                        <div class="form-text">{{ __('messages.patient_insurance.limit_hint') }}</div>
                    </div>
                    <div class="form-group col-sm-4 mb-5">
                        {{ Form::label('visit_limit', __('messages.patient_insurance.visit_limit') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('visit_limit', null, ['class' => 'form-control price-input']) }}
                    </div>
                    <div class="form-group col-sm-4 mb-5">
                        {{ Form::label('priority', __('messages.patient_insurance.priority') . ':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        {{ Form::number('priority', 1, ['class' => 'form-control', 'min' => 1, 'max' => 99, 'required']) }}
                        <div class="form-text">{{ __('messages.patient_insurance.priority_hint') }}</div>
                    </div>
                    <div class="form-group col-sm-8 mb-5">
                        {{ Form::label('notes', __('messages.patient_insurance.notes') . ':', ['class' => 'form-label']) }}
                        {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 1000]) }}
                    </div>
                    <div class="form-group col-sm-12 mb-5">
                        {{ Form::label('card_photo', __('messages.patient_insurance.card_photo') . ':', ['class' => 'form-label']) }}
                        <input type="file" name="card_photo" class="form-control" accept="image/*" capture="environment">
                        <div class="form-text">{{ __('messages.patient_insurance.card_photo_hint') }}</div>
                    </div>
                    <div class="form-group col-sm-12 mb-5">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="patientInsuranceActive" checked>
                            <label class="form-check-label" for="patientInsuranceActive">{{ __('messages.common.active') }}</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    {{ Form::button(__('messages.common.save'), ['type' => 'submit', 'class' => 'btn btn-primary me-2', 'id' => 'patientInsuranceSave']) }}
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
