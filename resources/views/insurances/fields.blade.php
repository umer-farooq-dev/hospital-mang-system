<div class="row">
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('name', __('messages.insurance.insurance') . ':', ['class' => 'form-label']) }}
        <span class="required"></span>
        {{ Form::text('name', null, ['class' => 'form-control ', 'required', 'placeholder' => __('messages.insurance.insurance')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('payer_type', __('messages.insurance.payer_type') . ':', ['class' => 'form-label']) }}
        <span class="required"></span>
        {{ Form::select('payer_type', $payerTypes, null, ['class' => 'form-select', 'required', 'data-control' => 'select2']) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('coverage_percent', __('messages.insurance.coverage_percent') . ':', ['class' => 'form-label']) }}
        ({{ __('messages.invoice.in_percentage') }}(%))
        {{ Form::number('coverage_percent', null, ['class' => 'form-control', 'min' => 0, 'max' => 100, 'step' => '.01', 'placeholder' => __('messages.insurance.coverage_percent')]) }}
        <div class="form-text">{{ __('messages.insurance.coverage_hint') }}</div>
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('service_tax', __('messages.insurance.service_tax') . ':', ['class' => 'form-label fs-6']) }}
        
        {{ Form::text('service_tax', null, ['class' => 'form-control  service-tax price-input', 'placeholder' => __('messages.insurance.service_tax')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('discount', __('messages.insurance.discount') . ': ', ['class' => 'form-label']) }}
        ({{ __('messages.invoice.in_percentage') }}(%))
        {{ Form::number('discount', null, ['id' => 'insuranceDiscountId', 'class' => 'form-control discount', 'min' => 0, 'max' => 100, 'step' => '.01', 'placeholder' => __('messages.insurance.discount')]) }}
        <div class="form-text">{{ __('messages.insurance.discount_hint') }}</div>
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('insurance_no', __('messages.insurance.insurance_no') . ':', ['class' => 'form-label']) }}
        
        {{ Form::text('insurance_no', null, ['class' => 'form-control', 'placeholder' => __('messages.insurance.insurance_no')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('insurance_code', __('messages.insurance.insurance_code') . ':', ['class' => 'form-label']) }}
        <span class="required"></span>
        {{ Form::text('insurance_code', null, ['class' => 'form-control', 'required', 'placeholder' => __('messages.insurance.insurance_code')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('hospital_rate', __('messages.insurance.hospital_rate') . ':', ['class' => 'form-label']) }}
        
        {{ Form::text('hospital_rate', null, ['class' => 'form-control hospital-rate price-input', 'placeholder' => __('messages.insurance.hospital_rate')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('billing_option', __('messages.insurance.billing_option') . ':', ['class' => 'form-label']) }}
        <span class="required"></span>
        {{ Form::select('billing_option', $billingOptions, null, ['class' => 'form-select', 'required', 'data-control' => 'select2']) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('credit_days', __('messages.insurance.credit_days') . ':', ['class' => 'form-label']) }}
        {{ Form::number('credit_days', null, ['class' => 'form-control', 'min' => 0, 'max' => 365, 'placeholder' => __('messages.insurance.credit_days')]) }}
    </div>
    <div class="form-group col-sm-12 mb-5">
        {{ Form::label('claim_note', __('messages.claims.claim_note') . ':', ['class' => 'form-label']) }}
        {{ Form::textarea('claim_note', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 2000]) }}
        <div class="form-text">{{ __('messages.claims.claim_note_hint') }}</div>
    </div>
    @php($claimColumns = isset($insurance) ? $insurance->claimColumns() : \App\Models\Insurance::DEFAULT_CLAIM_COLUMNS)
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('claim_title', __('messages.claims.claim_title') . ':', ['class' => 'form-label']) }}
        {{ Form::text('claim_title', null, ['class' => 'form-control', 'maxlength' => 100, 'placeholder' => __('messages.claims.invoice')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        <input type="hidden" name="claim_layout_sent" value="1">
        <label class="form-label">{{ __('messages.claims.claim_columns') }}:</label>
        <div class="d-flex flex-wrap gap-4">
            @foreach (\App\Models\Insurance::CLAIM_COLUMNS as $column)
                <label class="form-check form-check-inline mb-0">
                    <input type="checkbox" class="form-check-input" name="claim_columns[]" value="{{ $column }}" {{ in_array($column, $claimColumns) ? 'checked' : '' }}>
                    <span class="form-check-label">{{ __('messages.claims.columns.' . $column) }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('price_list_id', __('messages.insurance.price_list_id') . ':', ['class' => 'form-label']) }}
        {{ Form::select('price_list_id', $priceLists, null, ['class' => 'form-select', 'data-control' => 'select2', 'placeholder' => __('messages.insurance.price_list_default')]) }}
        <div class="form-text">{{ __('messages.insurance.price_list_hint') }}</div>
    </div>
    @foreach (['tax_id' => 50, 'trade_register' => 50, 'tax_regime' => 100, 'tax_division' => 100, 'account_code' => 30] as $field => $max)
        <div class="form-group col-sm-6 mb-5">
            {{ Form::label($field, __('messages.insurance.' . $field) . ':', ['class' => 'form-label']) }}
            {{ Form::text($field, null, ['class' => 'form-control', 'maxlength' => $max, 'placeholder' => __('messages.insurance.' . $field)]) }}
        </div>
    @endforeach
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('contact_person', __('messages.insurance.contact_person') . ':', ['class' => 'form-label']) }}
        {{ Form::text('contact_person', null, ['class' => 'form-control', 'maxlength' => 160, 'placeholder' => __('messages.insurance.contact_person')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('phone', __('messages.insurance.phone') . ':', ['class' => 'form-label']) }}
        {{ Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 30, 'placeholder' => __('messages.insurance.phone')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('email', __('messages.insurance.email') . ':', ['class' => 'form-label']) }}
        {{ Form::email('email', null, ['class' => 'form-control', 'maxlength' => 160, 'placeholder' => __('messages.insurance.email')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('address', __('messages.insurance.address') . ':', ['class' => 'form-label']) }}
        {{ Form::textarea('address', null, ['class' => 'form-control', 'rows' => 4, 'placeholder' => __('messages.insurance.address')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('remark', __('messages.insurance.remark') . ':', ['class' => 'form-label']) }}
        {{ Form::textarea('remark', null, ['class' => 'form-control', 'rows' => 4, 'placeholder' => __('messages.insurance.remark')]) }}
    </div>
    <div class="form-group col-sm-6 mb-5 d-flex flex-column">
        {{ Form::label('status', __('messages.common.status') . ':', ['class' => 'form-label fs-6 fw-']) }}
        <div class="form-check form-switch">
            <input class="form-check-input w-35px h-20px is-active {{getCurrentLoginUserLanguageName()== 'ar' ? 'float-end' : 'float-start'}}" name="status" type="checkbox" value="1"
                tabindex="8" {{ !isset($insurance) ? 'checked' : ($insurance->status ? 'checked' : '') }}>
        </div>
    </div>
    <div class="col-sm-12 mt-3 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="h5 mb-1">{{ __('messages.coverage.title') }}</div>
                <div class="text-muted fs-7">{{ __('messages.coverage.hint') }}</div>
            </div>
            <button type="button" class="btn btn-primary" id="addCoverageRule">{{ __('messages.common.add') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="coverageRulesTable" data-next-index="{{ isset($insurance) ? $insurance->coverageRules->count() : 0 }}">
                <thead>
                    <tr class="text-muted fw-bolder fs-7 text-uppercase">
                        <th>{{ __('messages.coverage.department') }}</th>
                        <th>{{ __('messages.coverage.charge_category') }}</th>
                        <th style="width: 130px">{{ __('messages.coverage.percent') }} %</th>
                        <th class="text-center">{{ __('messages.coverage.not_covered') }}</th>
                        <th class="text-center">{{ __('messages.coverage.needs_approval') }}</th>
                        <th style="width: 140px">{{ __('messages.coverage.max_amount') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (isset($insurance) ? $insurance->coverageRules : [] as $rule)
<tr class="coverage-row">
    <td>{{ Form::select('coverage[' . $loop->index . '][doctor_department_id]', $departments, $rule->doctor_department_id, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.coverage.all_departments')]) }}</td>
    <td>{{ Form::select('coverage[' . $loop->index . '][charge_category_id]', $chargeCategories, $rule->charge_category_id, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.coverage.any_category')]) }}</td>
    <td><input type="number" class="form-control form-control-sm" name="coverage[{{ $loop->index }}][coverage_percent]" value="{{ $rule->coverage_percent !== null ? $rule->coverage_percent + 0 : '' }}" min="0" max="100" step="0.01" placeholder="{{ __('messages.coverage.payer_percent') }}"></td>
    <td class="text-center"><input type="checkbox" class="form-check-input" name="coverage[{{ $loop->index }}][not_covered]" value="1" {{ $rule->not_covered ? 'checked' : '' }}></td>
    <td class="text-center"><input type="checkbox" class="form-check-input" name="coverage[{{ $loop->index }}][needs_approval]" value="1" {{ $rule->needs_approval ? 'checked' : '' }}></td>
    <td><input type="number" class="form-control form-control-sm" name="coverage[{{ $loop->index }}][max_amount]" value="{{ $rule->max_amount !== null ? $rule->max_amount + 0 : '' }}" min="0" step="any"></td>
    <td class="text-center"><a href="javascript:void(0)" class="delete-coverage-rule btn px-1 text-danger fs-3" title="{{ __('messages.common.delete') }}"><i class="fa-solid fa-trash"></i></a></td>
</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <template id="coverageRuleTemplate">
<tr class="coverage-row">
    <td>{{ Form::select('coverage[__INDEX__][doctor_department_id]', $departments, null, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.coverage.all_departments')]) }}</td>
    <td>{{ Form::select('coverage[__INDEX__][charge_category_id]', $chargeCategories, null, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.coverage.any_category')]) }}</td>
    <td><input type="number" class="form-control form-control-sm" name="coverage[__INDEX__][coverage_percent]" value="" min="0" max="100" step="0.01" placeholder="{{ __('messages.coverage.payer_percent') }}"></td>
    <td class="text-center"><input type="checkbox" class="form-check-input" name="coverage[__INDEX__][not_covered]" value="1" ></td>
    <td class="text-center"><input type="checkbox" class="form-check-input" name="coverage[__INDEX__][needs_approval]" value="1" ></td>
    <td><input type="number" class="form-control form-control-sm" name="coverage[__INDEX__][max_amount]" value="" min="0" step="any"></td>
    <td class="text-center"><a href="javascript:void(0)" class="delete-coverage-rule btn px-1 text-danger fs-3" title="{{ __('messages.common.delete') }}"><i class="fa-solid fa-trash"></i></a></td>
</tr>
        </template>
    </div>
    <div class="col-sm-12 mt-3">
        <div class="row">
            <div class="col-lg-8 mb-3 h5">
                {{ __('messages.insurance.disease_details') }}
            </div>
            <div class="col-lg-4">
                <div class="col-lg-12 col-md-12 col-sm-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary text-star"
                        id="addInsuranceItem">{{ __('messages.common.add') }}</button>
                </div>
            </div>
        </div>
        <div class="table-responsive-sm">
            <table class="table table-striped" id="insuranceBillTbl">
                <thead>
                    <tr class="{{getCurrentLoginUserLanguageName()=='ar' ? 'text-end' : 'text-start'}} text-muted fw-bolder fs-7 text-uppercase gs-0">
                        <th class="text-center">#</th>
                        <th class="insurance-name form-label fs-6 fw-bolder text-gray-700 mb-3'">
                            {{ __('messages.insurance.diseases_name') }}
                        </th>
                        <th class="insurance-name form-label fs-6 fw-bolder text-gray-700 mb-3'">
                            {{ __('messages.insurance.diseases_charge') }}
                        </th>
                        <th class="table__add-btn-heading text-center form-label fs-6 fw-bolder text-gray-700 mb-3">
                            {{ __('messages.common.action') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="disease-item-container">
                    @if (isset($diseases))
                        @foreach ($diseases as $disease)
                            <tr>
                                <td class="text-center item-number">{{ $loop->iteration }}</td>
                                <td>
                                    {{ Form::text('disease_name[]', $disease->disease_name, ['class' => 'form-control disease-name ', 'placeholder' => __('messages.insurance.diseases_name')]) }}
                                </td>
                                <td>
                                    {{ Form::text('disease_charge[]', inputAmount($disease->disease_charge), [
                                        'class' => 'form-control  disease-charge  price-input',
                                        'placeholder' => __('messages.insurance.diseases_charge'),
                                    ]) }}
                                </td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" title="{{ __('messages.common.delete') }}"
                                        class="delete-disease btn px-1 text-danger fs-3 pe-0">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="text-center item-number">1</td>
                            <td>
                                {{ Form::text('disease_name[]', null, ['class' => 'form-control  disease-name', 'placeholder' => __('messages.insurance.diseases_name')]) }}
                            </td>
                            <td>
                                {{ Form::text('disease_charge[]', null, ['class' => 'form-control disease-charge price-input', 'placeholder' => __('messages.insurance.diseases_charge')]) }}
                            </td>
                            <td class="text-center">
                                <a href="javascript:void(0)" title="{{ __('messages.common.delete') }}"
                                    class="delete-disease btn px-1 text-danger fs-3 pe-0">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="{{getCurrentLoginUserLanguageName()=='ar' ? 'float-start' : 'float-end'}} p-0 mb-3">
                <table>
                    <tbody>
                        <tr>
                            <td class="font-weight-bold form-label fs-6 fw-bolder text-gray-700 mb-3">
                                {{ __('messages.insurance.total_amount') . ':' }}</td>
                            <td class="{{getCurrentLoginUserLanguageName()=='ar' ? 'text-left' : 'text-right'}}">
                                <span id="insuranceTotal"
                                    class="totalAmount {{getCurrentLoginUserLanguageName()=='ar' ? 'me-2' : 'ms-2'}}">{{ isset($insurance) ? getCurrencySymbol() . '' . inputAmount($insurance->total) : getCurrencySymbol() . '' . 0 }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Total Amount Field -->
    {{ Form::hidden('total', null, ['class' => 'form-control', 'id' => 'insuranceTotal_amount']) }}
    <div class="d-flex justify-content-end">
        {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-2', 'id' => 'insuranceSaveBtn']) }}
        <a href="{{ route('insurances.index') }}"
            class="btn btn-secondary me-2">{{ __('messages.common.cancel') }}</a>
    </div>
</div>
