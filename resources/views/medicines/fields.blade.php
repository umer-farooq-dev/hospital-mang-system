{{ Form::hidden('currency_symbol', getCurrentCurrency(), ['class' => 'currencySymbol']) }}
<!-- Name Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('name', __('messages.medicine.medicine') . ':', ['class' => 'form-label']) }}
    <span class="required"></span>
    {{ Form::text('name', null, ['class' => 'form-control','minlength' => 2, 'id' => 'medicineNameId','placeholder'=>__('messages.medicine.medicine')]) }}
</div>

<!-- Category Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('category_id', __('messages.medicine.category') . ':', ['class' => 'form-label']) }}
    <span class="required"></span>
    {{ Form::select('category_id', $categories, isset($medicine) ? $medicine->category_id : null, ['class' => 'form-select', 'placeholder' => __('messages.medicine.select_category'), 'id' => 'medicineCategoryId']) }}
</div>

<!-- Quantity Field -->

{{ Form::hidden('quantity', isset($medicine) ? $medicine->quantity : 0, ['class' => 'form-control', 'placeholder' => __('messages.item_stock.quantity'), 'id' => 'quantityId']) }}

<!-- Name Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('brand_id', __('messages.medicine.brand') . ':', ['class' => 'form-label']) }}
    <span class="required"></span>
    {{ Form::select('brand_id', $brands, isset($medicine) ? $medicine->brand_id : null, ['class' => 'form-select', 'placeholder' => __('messages.medicine.select_brand'), 'id' => 'medicineBrandId']) }}
</div>

<!-- Salt Composition Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('salt_composition', __('messages.medicine.salt_composition').(':'), ['class' => 'form-label']) }}
    <span
        class="required"></span>
    {{ Form::text('salt_composition', null, ['class' => 'form-control','required','placeholder'=>__('messages.medicine.salt_composition')]) }}
</div>

<div class="form-group col-md-6 mb-5">
    {{ Form::label('common_name', __('messages.stock.common_name') . ':', ['class' => 'form-label']) }}
    {{ Form::text('common_name', null, ['class' => 'form-control', 'maxlength' => 191]) }}
</div>
<div class="form-group col-md-3 mb-5">
    {{ Form::label('barcode', __('messages.stock.barcode') . ':', ['class' => 'form-label']) }}
    {{ Form::text('barcode', null, ['class' => 'form-control', 'maxlength' => 100]) }}
</div>
<div class="form-group col-md-3 mb-5">
    {{ Form::label('cis_code', __('messages.stock.cis_code') . ':', ['class' => 'form-label']) }}
    {{ Form::text('cis_code', null, ['class' => 'form-control', 'maxlength' => 50]) }}
</div>
@foreach (['unit' => 'medicine_unit', 'dosage_form' => 'dosage_form', 'route' => 'medicine_route'] as $field => $list)
    <div class="form-group col-md-4 mb-5">
        {{ Form::label($field, __('messages.stock.' . $field) . ':', ['class' => 'form-label']) }}
        {{ Form::select($field, \App\Models\MasterListItem::options($list, $medicine->{$field} ?? null), $medicine->{$field} ?? null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}
    </div>
@endforeach

<!-- Buying Price Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('buying_price', __('messages.medicine.buying_price') . ':', ['class' => 'form-label']) }}
    <span class="required"></span>
    {{ Form::text('buying_price', null, ['class' => 'form-control price-input','placeholder'=>__('messages.medicine.buying_price')]) }}
</div>

<!-- Selling Price Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('selling_price', __('messages.medicine.selling_price') . ':', ['class' => 'form-label']) }}
    <span class="required"></span>
    {{ Form::text('selling_price', null, ['class' => 'form-control price-input','placeholder'=>__('messages.medicine.selling_price')]) }}
</div>

<!-- Effect Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('side_effects', __('messages.medicine.side_effects').(':'), ['class' => 'form-label']) }}
    {{ Form::textarea('side_effects', null, ['class' => 'form-control', 'rows'=>4,'placeholder'=>__('messages.medicine.side_effects')]) }}
</div>

<!-- Effect Field -->
<div class="form-group col-md-6 mb-5">
    {{ Form::label('description', __('messages.medicine.description').(':'), ['class' => 'form-label']) }}
    {{ Form::textarea('description', null, ['class' => 'form-control', 'rows'=>4,'placeholder'=>__('messages.medicine.description')]) }}
</div>

<!-- Submit Field -->
<div class="d-flex justify-content-end">
    {{ Form::submit(__('messages.common.save'), ['class' => (getCurrentLoginUserLanguageName()=='ar' ? 'btn btn-primary ms-2' : 'btn btn-primary me-2'), 'id' => 'medicineSave']) }}
    <a href="{{ route('medicines.index') }}" class="btn btn-secondary">{{ __('messages.common.cancel') }}</a>
</div>
