<div id="edit_front_service_modal" class="modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ __('messages.common.edit') }} : {{ __('messages.front_web.type_'.$type) }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{ Form::open(['id' => 'editFrontServiceForm', 'files' => true]) }}
            <div class="modal-body">
                <div class="alert alert-danger d-none hide" id="editFrontServiceErrorsBox"></div>
                {{ Form::hidden('id', null, ['id' => 'frontServiceId']) }}
                {{ Form::hidden('type', $type) }}
                <div class="row">
                    <div class="form-group col-sm-8 mb-5">
                        {{ Form::label('name', __('messages.front_web.title').':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        {{ Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 191, 'id' => 'editFrontServiceName', 'placeholder' => __('messages.front_web.title')]) }}
                    </div>
                    <div class="form-group col-sm-4 mb-5">
                        {{ Form::label('sort_order', __('messages.front_web.sort_order').':', ['class' => 'form-label']) }}
                        {{ Form::number('sort_order', 0, ['class' => 'form-control', 'min' => 0, 'id' => 'editFrontServiceSortOrder', ]) }}
                    </div>
                    @if ($type != \App\Models\FrontService::SERVICE)
                        <div class="form-group col-sm-6 mb-5">
                            {{ Form::label('category', __('messages.front_web.category').':', ['class' => 'form-label']) }}
                            {{ Form::text('category', null, ['class' => 'form-control', 'maxlength' => 100, 'id' => 'editFrontServiceCategory', 'placeholder' => __('messages.front_web.category')]) }}
                        </div>
                    @endif
                    @if ($type == \App\Models\FrontService::POST)
                        <div class="form-group col-sm-6 mb-5">
                            {{ Form::label('published_at', __('messages.front_web.published_at').':', ['class' => 'form-label']) }}
                            {{ Form::date('published_at', now()->toDateString(), ['class' => 'form-control', 'id' => 'editFrontServicePublishedAt', ]) }}
                        </div>
                    @endif
                    <div class="form-group col-sm-12 mb-5">
                        {{ Form::label('short_description', __('messages.short_description').':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        {{ Form::textarea('short_description', null, ['class' => 'form-control', 'rows' => 3, 'required', 'id' => 'editFrontServiceDescription', 'placeholder' => __('messages.short_description')]) }}
                    </div>
                    <div class="form-group col-sm-12 mb-5">
                        {{ Form::label('description', __('messages.front_web.body').':', ['class' => 'form-label']) }}
                        {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 8, 'id' => 'editFrontServiceBody', 'placeholder' => __('messages.front_web.body')]) }}
                        <div class="form-text">{{ __('messages.front_web.body_hint') }}</div>
                    </div>
                    @if ($type == \App\Models\FrontService::SERVICE)
                    <div class="form-group col-sm-6 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('icon', __('messages.icon').':', ['class' => 'form-label']) }}
                            
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" id="editFrontServicePreviewImage" style="background-image: url({{ asset('web_front/images/services/medicine.png') }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.icon') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('icon', ['class' => 'image-upload d-none document-file', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="form-group col-sm-6 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('image', __('messages.front_web.image').':', ['class' => 'form-label']) }}
                            
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" id="editFrontServicePreviewPicture" style="background-image: url({{ asset('web_front/images/services/medicine.png') }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.front_web.image') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('image', ['class' => 'image-upload d-none document-file', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 form-text">{{ __('messages.front_web.image_hint') }}</div>
                </div>
            </div>
            <div class="modal-footer pt-0">
                {{ Form::button(__('messages.common.save'), ['type' => 'submit', 'class' => 'btn btn-primary m-0', 'id' => 'editFrontServiceSave', 'data-loading-text' => "<span class='spinner-border spinner-border-sm'></span> Processing..."]) }}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
