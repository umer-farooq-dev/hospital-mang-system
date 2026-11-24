@extends('front_settings.index')
@section('title')
    {{ __('messages.front_settings') }}
@endsection
@section('section')
    <div class="card">
        <div class="card-header pb-0">
            <div class="card-title m-0">
                <h3>{{ __('messages.front_setting.front_setting_details') }}</h3>
            </div>
        </div>
        <div class="card-body pt-3">
            {{ Form::open(['route' => ['front.settings.update'], 'method' => 'post', 'files' => true, 'id' => 'addCMSForm']) }}
            {{ Form::hidden('sectionName', $sectionName) }}
            <div class="alert alert-danger d-none hide" id="homeErrorsBox"></div>
            <div class="row">
                <!-- Home Image Field -->
                <div class="form-group col-sm-6 mb-5">
                    <div class="row2" io-image-input="true">
                        {{ Form::label('image', __('messages.front_setting.home_page_image') . ':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        <div class="d-block">
                            <div class="image-picker">
                                <div class="image previewImage" id="homePreviewImage"
                                    style="background-image: url({{ $frontSettings['home_page_image'] ? $frontSettings['home_page_image'] : asset('web_front/images/main-banner/banner-one/woman-doctor.png') }})">
                                    <span class="picker-edit rounded-circle text-gray-500 fs-small"
                                        title="{{ __('messages.common.change_profile') }}">
                                        <label>
                                            <i class="fa-solid fa-pen" id="profileImageIcon"></i>
                                            {{ Form::file('home_page_image', ['id' => 'homePageImage', 'class' => 'image-upload d-none homePageImage', 'accept' => '.png, .jpg, .jpeg']) }}
                                            <input type="hidden" name="avatar_remove" />
                                        </label>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-text">{{ __('messages.allow_file_type') }}</div>
                </div>

                <!-- Certified Doctor Image Field -->
                <div class="form-group col-sm-6 mb-5">
                    <div class="row2" io-image-input="true">
                        {{ Form::label('image', __('messages.front_setting.home_page_certified_doctor_image') . ':', ['class' => 'form-label']) }}
                        <span class="required"></span>
                        <div class="d-block">
                            <div class="image-picker">
                                <div class="image previewImage" id="homeDoctorPreviewImage"
                                    style="background-image: url({{ $frontSettings['home_page_certified_doctor_image'] ? $frontSettings['home_page_certified_doctor_image'] : asset('web_front/images/healthcare-doctor/doctor-1.png') }})">
                                    <span class="picker-edit rounded-circle text-gray-500 fs-small"
                                        title="{{ __('messages.common.change_profile') }}">
                                        <label>
                                            <i class="fa-solid fa-pen" id="profileImageIcon"></i>
                                            {{ Form::file('home_page_certified_doctor_image', ['id' => 'homeDoctorImage', 'class' => 'image-upload d-none homeDoctorImage', 'accept' => '.png, .jpg, .jpeg']) }}
                                            <input type="hidden" name="avatar_remove" />
                                        </label>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-text">{{ __('messages.allow_file_type') }}</div>
                </div>
            </div>

            <div class="row mt-3 mb-5">
                <!-- Home Experience Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_experience', __('messages.front_setting.home_page_experience') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_experience', $frontSettings['home_page_experience'], ['placeholder' => __('messages.front_setting.home_page_experience'),'class' => 'form-control', 'required', 'id' => 'homePageExperience']) }}
                </div>

                <!-- Home Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_title', __('messages.front_setting.home_page_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_title', $frontSettings['home_page_title'], ['placeholder' => __('messages.front_setting.home_page_title'),'class' => 'form-control', 'required', 'id' => 'homeTitleId']) }}
                </div>

                <!-- Home description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_description', __('messages.front_setting.home_page_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_description', $frontSettings['home_page_description'], ['placeholder' => __('messages.front_setting.home_page_description'),'class' => 'form-control', 'required', 'rows' => 5, 'id' => 'homeShortDescription']) }}
                </div>

                <!-- Home Box Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_box_title', __('messages.front_setting.home_page_box_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_box_title', $frontSettings['home_page_box_title'], ['placeholder' => __('messages.front_setting.home_page_box_title'),'class' => 'form-control', 'required', 'id' => 'homePageBoxTitle']) }}
                </div>

                <!-- Home Box description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_box_description', __('messages.front_setting.home_page_box_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_box_description', $frontSettings['home_page_box_description'], ['placeholder' => __('messages.front_setting.home_page_box_description'),'class' => 'form-control', 'required', 'rows' => 5, 'id' => 'homePageBoxDes']) }}
                </div>

                <!-- Certified Doctor Text Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_certified_doctor_text', __('messages.front_setting.home_page_certified_doctor_text').':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_certified_doctor_text', $frontSettings['home_page_certified_doctor_text'], ['class' => 'form-control', 'required', 'id' => 'homeDoctorTextId']) }}
                </div>
                <!-- Certified Doctor Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_certified_doctor_title', __('messages.front_setting.home_page_certified_doctor_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_certified_doctor_title', $frontSettings['home_page_certified_doctor_title'], ['placeholder' => __('messages.front_setting.home_page_certified_doctor_title'),'class' => 'form-control', 'required', 'id' => 'homeDoctorTitleId']) }}
                </div>

                <!-- Certified Doctor Description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_certified_doctor_description', __('messages.front_setting.home_page_certified_doctor_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_certified_doctor_description', $frontSettings['home_page_certified_doctor_description'], ['placeholder' => __('messages.front_setting.home_page_certified_doctor_description'),'class' => 'form-control', 'required', 'rows' => 5, 'id' => 'homeDoctorDescription']) }}
                </div>

                <!-- Certified Box Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_certified_box_title', __('messages.front_setting.home_page_certified_box_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_certified_box_title', $frontSettings['home_page_certified_box_title'], ['placeholder' => __('messages.front_setting.home_page_certified_box_title'),'class' => 'form-control', 'required', 'id' => 'homePageCerBoxTl']) }}
                </div>

                <!-- Certified Box description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_certified_box_description', __('messages.front_setting.home_page_certified_box_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_certified_box_description', $frontSettings['home_page_certified_box_description'], ['placeholder' => __('messages.front_setting.home_page_certified_box_description'),'class' => 'form-control', 'required', 'rows' => 5, 'id' => 'homePageCerBoxDes']) }}
                </div>
            </div>

            {{-- Step --}}
            <div class="row mt-3 mb-5">
                <!-- Step 1 Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_1_title', __('messages.front_setting.home_page_step_1_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_step_1_title', $frontSettings['home_page_step_1_title'], ['placeholder' => __('messages.front_setting.home_page_step_1_title'),'class' => 'form-control', 'required', 'id' => 'homePageStep1Tl']) }}
                </div>

                <!-- Step 1 description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_1_description', __('messages.front_setting.home_page_step_1_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_step_1_description', $frontSettings['home_page_step_1_description'], ['placeholder' => __('messages.front_setting.home_page_step_1_description'),'class' => 'form-control', 'required', 'rows' => 5, 'onkeypress' => 'return avoidSpace(event);', 'id' => 'homePageStep1Des']) }}
                </div>

                <!-- Step 2 Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_2_title', __('messages.front_setting.home_page_step_2_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_step_2_title', $frontSettings['home_page_step_2_title'], ['placeholder' => __('messages.front_setting.home_page_step_2_title'),'class' => 'form-control', 'required', 'id' => 'homePageStep2Tl']) }}
                </div>

                <!-- Step 2 description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_2_description', __('messages.front_setting.home_page_step_2_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_step_2_description', $frontSettings['home_page_step_2_description'], ['placeholder' => __('messages.front_setting.home_page_step_2_description'),'class' => 'form-control', 'required', 'rows' => 5, 'onkeypress' => 'return avoidSpace(event);', 'id' => 'homePageStep2Des']) }}
                </div>

                <!-- Step 3 Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_3_title', __('messages.front_setting.home_page_step_3_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_step_3_title', $frontSettings['home_page_step_3_title'], ['placeholder' => __('messages.front_setting.home_page_step_3_title'),'class' => 'form-control', 'required', 'id' => 'homePageStep3Tl']) }}
                </div>

                <!-- Step 3 description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_3_description', __('messages.front_setting.home_page_step_3_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_step_3_description', $frontSettings['home_page_step_3_description'], ['placeholder' => __('messages.front_setting.home_page_step_3_description'),'class' => 'form-control', 'required', 'rows' => 5, 'onkeypress' => 'return avoidSpace(event);', 'id' => 'homePageStep3Des']) }}
                </div>

                <!-- Step 4 Title Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_4_title', __('messages.front_setting.home_page_step_4_title') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::text('home_page_step_4_title', $frontSettings['home_page_step_4_title'], ['placeholder' => __('messages.front_setting.home_page_step_4_title'),'class' => 'form-control', 'required', 'id' => 'homePageStep4Tl']) }}
                </div>

                <!-- Step 4 description Field -->
                <div class="form-group col-sm-12 mb-5">
                    {{ Form::label('home_page_step_4_description', __('messages.front_setting.home_page_step_4_description') . ':', ['class' => 'form-label']) }}
                    <span class="required"></span>
                    {{ Form::textarea('home_page_step_4_description', $frontSettings['home_page_step_4_description'], ['placeholder' => __('messages.front_setting.home_page_step_4_description'),'class' => 'form-control', 'required', 'rows' => 5, 'onkeypress' => 'return avoidSpace(event);', 'id' => 'homePageStep4Des']) }}
                </div>
            </div>
            {{-- Website design (Kliniko): colours, sections, headings and pictures --}}
            @php
                $themeImages = ['page_banner_image' => 'bg/breadcrumb-bg.jpg', 'home_page_video_image' => 'bg/video-h3-bg.jpg',
                    'home_page_step_1_image' => 'service/proces-h3-img1.jpg', 'home_page_step_2_image' => 'service/proces-h3-img2.jpg',
                    'home_page_step_3_image' => 'service/proces-h3-img1.jpg', 'home_page_step_4_image' => 'service/proces-h3-img2.jpg'];
                $homeSections = \App\Models\FrontSetting::homeSections($frontSettings);
            @endphp
            <h3 class="mt-3 mb-5">{{ __('messages.front_web.appearance') }}</h3>
            <div class="row mb-5">
                @foreach (\App\Models\FrontSetting::COLOR_KEYS as $colorKey)
                    <div class="form-group col-6 col-md mb-5">
                        {{ Form::label($colorKey, __('messages.front_web.'.$colorKey).':', ['class' => 'form-label']) }}
                        <input type="color" name="{{ $colorKey }}" id="{{ $colorKey }}" value="{{ $frontSettings[$colorKey] ?? '#009E49' }}" class="form-control form-control-color w-100">
                    </div>
                @endforeach
                <div class="col-12 form-text mt-n3">{{ __('messages.front_web.colors_hint') }}</div>
            </div>

            <h3 class="mb-5">{{ __('messages.front_web.website_languages') }}</h3>
            <div class="row mb-5">
                <div class="form-group col-md-4 mb-5">
                    {{ Form::label('website_default_lang', __('messages.front_web.default_language').':', ['class' => 'form-label']) }}
                    {{ Form::select('website_default_lang', getLanguages(), $frontSettings['website_default_lang'] ?? 'fr', ['class' => 'form-select']) }}
                </div>
                <div class="form-group col-md-8 mb-5">
                    {{ Form::label('website_languages', __('messages.front_web.other_languages').':', ['class' => 'form-label d-block']) }}
                    <input type="hidden" name="website_languages[]" value="">
                    @foreach (getLanguages() as $code => $language)
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="website_languages[]" value="{{ $code }}" {{ in_array($code, explode(',', $frontSettings['website_languages'] ?? 'fr,en')) ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $language }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <h3 class="mb-5">{{ __('messages.front_web.home_sections') }}</h3>
            <div class="table-responsive mb-5">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('messages.front_web.section') }}</th>
                            <th class="w-100px">{{ __('messages.front_web.show') }}</th>
                            <th class="w-100px">{{ __('messages.front_web.sort_order') }}</th>
                            <th>{{ __('messages.front_web.section_sub') }}</th>
                            <th>{{ __('messages.front_web.section_title') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($homeSections as $index => $homeSection)
                            @php($sectionKey = $homeSection['key'])
                            <tr>
                                <td class="fw-bold">{{ __('messages.front_web.section_'.$sectionKey) }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="home_sections[{{ $sectionKey }}][on]" value="0">
                                        <input class="form-check-input" type="checkbox" name="home_sections[{{ $sectionKey }}][on]" value="1" {{ $homeSection['on'] ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td><input type="number" min="0" class="form-control" name="home_sections[{{ $sectionKey }}][order]" value="{{ $index + 1 }}"></td>
                                <td>
                                    @if (array_key_exists('section_'.$sectionKey.'_sub', $frontSettings))
                                        {{ Form::text('section_'.$sectionKey.'_sub', $frontSettings['section_'.$sectionKey.'_sub'], ['class' => 'form-control', 'maxlength' => 255]) }}
                                    @endif
                                </td>
                                <td>
                                    @if (array_key_exists('section_'.$sectionKey.'_title', $frontSettings))
                                        {{ Form::text('section_'.$sectionKey.'_title', $frontSettings['section_'.$sectionKey.'_title'], ['class' => 'form-control', 'maxlength' => 255]) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="form-text">{{ __('messages.front_web.sections_hint') }}</div>
            </div>

            <h3 class="mb-5">{{ __('messages.front_web.home_extras') }}</h3>
            <div class="row mb-5">
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('home_page_subtitle', __('messages.front_web.hero_subtitle').':', ['class' => 'form-label']) }}
                        {{ Form::text('home_page_subtitle', $frontSettings['home_page_subtitle'] ?? '', ['class' => 'form-control', 'maxlength' => 255]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('home_page_video_url', __('messages.front_web.video_url').':', ['class' => 'form-label']) }}
                        {{ Form::text('home_page_video_url', $frontSettings['home_page_video_url'] ?? '', ['class' => 'form-control', 'maxlength' => 255]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('home_page_experience_label', __('messages.front_web.experience_label').':', ['class' => 'form-label']) }}
                        {{ Form::text('home_page_experience_label', $frontSettings['home_page_experience_label'] ?? '', ['class' => 'form-control', 'maxlength' => 255]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('home_page_circle_text', __('messages.front_web.circle_text').':', ['class' => 'form-label']) }}
                        {{ Form::text('home_page_circle_text', $frontSettings['home_page_circle_text'] ?? '', ['class' => 'form-control', 'maxlength' => 255]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('home_page_about_points', __('messages.front_web.about_points').':', ['class' => 'form-label']) }}
                        {{ Form::textarea('home_page_about_points', $frontSettings['home_page_about_points'] ?? '', ['class' => 'form-control', 'rows' => 4]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('section_process_text', __('messages.front_web.process_text').':', ['class' => 'form-label']) }}
                        {{ Form::textarea('section_process_text', $frontSettings['section_process_text'] ?? '', ['class' => 'form-control', 'rows' => 4]) }}
                    </div>
                    <div class="form-group col-sm-6 mb-5">
                        {{ Form::label('section_appointment_sub', __('messages.front_web.appointment_sub').':', ['class' => 'form-label']) }}
                        {{ Form::text('section_appointment_sub', $frontSettings['section_appointment_sub'] ?? '', ['class' => 'form-control', 'maxlength' => 255]) }}
                    </div>
            </div>
            <div class="row mb-5">
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('page_banner_image', __('messages.front_web.page_banner_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('page_banner_image', $themeImages['page_banner_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('page_banner_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('home_page_video_image', __('messages.front_web.video_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('home_page_video_image', $themeImages['home_page_video_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('home_page_video_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('home_page_step_1_image', __('messages.front_web.step_1_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('home_page_step_1_image', $themeImages['home_page_step_1_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('home_page_step_1_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('home_page_step_2_image', __('messages.front_web.step_2_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('home_page_step_2_image', $themeImages['home_page_step_2_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('home_page_step_2_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('home_page_step_3_image', __('messages.front_web.step_3_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('home_page_step_3_image', $themeImages['home_page_step_3_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('home_page_step_3_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3 mb-5">
                        <div class="row2" io-image-input="true">
                            {{ Form::label('home_page_step_4_image', __('messages.front_web.step_4_image').':', ['class' => 'form-label']) }}
                            <div class="d-block">
                                <div class="image-picker">
                                    <div class="image previewImage" style="background-image: url({{ frontImage('home_page_step_4_image', $themeImages['home_page_step_4_image']) }})">
                                        <span class="picker-edit rounded-circle text-gray-500 fs-small" title="{{ __('messages.common.change') }}">
                                            <label>
                                                <i class="fa-solid fa-pen"></i>
                                                {{ Form::file('home_page_step_4_image', ['class' => 'image-upload d-none', 'accept' => '.png, .jpg, .jpeg, .webp']) }}
                                            </label>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="col-12 form-text">{{ __('messages.front_web.image_hint') }}</div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
                {{ Form::hidden('termConditionData', $frontSettings['terms_conditions'], ['class' => 'TermConditionData']) }}
                {{ Form::hidden('privacyPolicyData', $frontSettings['privacy_policy'], ['class' => 'PrivacyPolicyData']) }}
                <!-- Submit Field -->
                <div class="d-flex justify-content-end">
                    {{ Form::submit(__('messages.common.save'), ['class' => (getCurrentLoginUserLanguageName() == 'ar' ? 'btn btn-primary ms-3' : 'btn btn-primary  me-3')]) }}
                    {{ Form::reset(__('messages.common.cancel'), ['class' => 'btn btn-secondary']) }}
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
@endsection
@section('scripts')
    {{--    assets/js/front_settings/cms/create-edit.js --}}
@endsection
