<?php

namespace App\Http\Controllers;

use App\Models\FrontSetting;
use App\Repositories\FrontSettingRepository;
use Flash;
use Illuminate\Http\Request;

class FrontSettingController extends AppBaseController
{
    /** @var FrontSettingRepository */
    private $frontSettingRepository;

    const SECTIONS = ['cms', 'about-us', 'appointment', 'terms-and-conditions', 'map'];

    public function __construct(FrontSettingRepository $frontSettingRepository)
    {
        $this->frontSettingRepository = $frontSettingRepository;
    }

    public function index(Request $request)
    {
        $frontSettings = FrontSetting::pluck('value', 'key')->toArray();
        $sectionName = in_array($request->section, self::SECTIONS, true) ? $request->section : 'cms';

        return view("front_settings.$sectionName", compact('frontSettings', 'sectionName'));
    }

    public function update(Request $request)
    {
        $image = 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096';
        $request->validate(array_merge(
            array_fill_keys(array_keys(FrontSetting::IMAGE_KEYS), $image),
            array_fill_keys(FrontSetting::COLOR_KEYS, ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']),
            ['home_page_video_url' => 'nullable|url|max:255', 'home_sections' => 'nullable|array',
                'website_default_lang' => 'nullable|in:'.implode(',', array_keys(getLanguages())), 'website_languages' => 'nullable|array'],
        ));
        $this->frontSettingRepository->updateFrontSetting($request->all());

        Flash::success(__('messages.front_settings').' '.__('messages.common.saved_successfully'));

        return redirect(route('front.settings.index', ['section' => in_array($request->sectionName, self::SECTIONS, true) ? $request->sectionName : 'cms']));
    }
}
