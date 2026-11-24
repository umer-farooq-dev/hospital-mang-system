<?php

namespace App\Repositories;

use App\Models\FrontSetting;
use Arr;

class FrontSettingRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'key',
        'value',
        'type',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return FrontSetting::class;
    }

    public function updateFrontSetting($input)
    {
        foreach (FrontSetting::IMAGE_KEYS as $key => $collection) {
            if (empty($input[$key])) {
                continue;
            }
            $frontSetting = FrontSetting::firstOrCreate(['key' => $key], ['value' => '', 'type' => FrontSetting::HOME_PAGE]);
            $frontSetting->clearMediaCollection($collection);
            $frontSetting->addMedia($input[$key])->toMediaCollection($collection, config('app.media_disc'));
            $frontSetting->update(['value' => $frontSetting->getFirstMediaUrl($collection)]);
        }

        if (isset($input['home_sections']) && is_array($input['home_sections'])) {
            $known = array_column(FrontSetting::HOME_SECTIONS, 'key');
            $sections = collect($input['home_sections'])
                ->filter(fn ($s, $key) => in_array($key, $known, true))
                ->map(fn ($s, $key) => ['key' => $key, 'on' => empty($s['on']) ? 0 : 1, 'order' => (int) ($s['order'] ?? 0)])
                ->sortBy('order')->values()->map(fn ($s) => ['key' => $s['key'], 'on' => $s['on']])->all();
            $input['home_sections'] = json_encode($sections);
        }

        $frontSettingInputArray = Arr::only($input, [
            'about_us_title', 'about_us_mission', 'about_us_description',
            'home_page_experience', 'home_page_title', 'home_page_description', 'home_page_certified_doctor_text',
            'home_page_certified_doctor_title', 'home_page_certified_doctor_description', 'terms_conditions',
            'privacy_policy',
            'home_page_box_title', 'home_page_box_description', 'home_page_certified_box_title',
            'home_page_certified_box_description',
            'home_page_step_1_title', 'home_page_step_2_title', 'home_page_step_3_title', 'home_page_step_4_title',
            'home_page_step_1_description', 'home_page_step_2_description', 'home_page_step_3_description',
            'home_page_step_4_description',
            'appointment_title', 'appointment_description', 'location_map',
            'home_page_subtitle', 'home_page_video_url', 'home_page_experience_label', 'home_page_circle_text',
            'home_page_about_points', 'home_sections', 'section_process_text', 'section_appointment_sub',
            'section_services_sub', 'section_services_title', 'section_process_sub', 'section_process_title',
            'section_doctors_sub', 'section_doctors_title', 'section_cases_sub', 'section_cases_title',
            'section_testimonials_sub', 'section_testimonials_title', 'section_blog_sub', 'section_blog_title',
            ...FrontSetting::COLOR_KEYS,
        ]);

        foreach ($frontSettingInputArray as $key => $value) {
            FrontSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'type' => FrontSetting::where('key', $key)->value('type') ?? FrontSetting::HOME_PAGE]
            );
        }
    }
}
