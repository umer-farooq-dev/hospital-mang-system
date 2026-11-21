<div id="add_master_list_item_modal" class="modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ __('messages.master_list.new_item') }}: {{ __('messages.master_list.types.'.$type) }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{ Form::open(['id' => 'addMasterListItemForm']) }}
            <div class="modal-body">
                <div class="alert alert-danger d-none hide" id="masterListItemErrorsBox"></div>
                {{ Form::hidden('type', $type) }}
                <div class="row">
                    <div class="form-group col-sm-12 mb-5">
                        {{ Form::label('name', __('messages.master_list.name').':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        {{ Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 191, 'placeholder' => __('messages.master_list.name')]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('sort_order', __('messages.master_list.sort_order').':', ['class' => 'form-label']) }}
                        {{ Form::number('sort_order', 0, ['class' => 'form-control', 'min' => 0, 'max' => 9999]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('is_active', __('messages.common.status').':', ['class' => 'form-label']) }}
                        <div class="form-check form-switch">
                            <input class="form-check-input w-35px h-20px" name="is_active" type="checkbox" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer pt-0">
                {{ Form::button(__('messages.common.save'), ['type' => 'submit', 'class' => 'btn btn-primary m-0', 'id' => 'masterListItemSave', 'data-loading-text' => "<span class='spinner-border spinner-border-sm'></span> Processing..."]) }}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
