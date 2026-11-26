<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Setting;
use App\Queries\ModuleDataTable;
use App\Repositories\SettingRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class SettingController extends AppBaseController
{
    /** @var SettingRepository */
    private $settingRepository;

    public function __construct(SettingRepository $settingRepo)
    {
        $this->settingRepository = $settingRepo;
    }

    public function edit(Request $request)
    {
        $settings = $this->settingRepository->getSyncList();
        $currencies = getCurrencies();
        $statusArr = Module::STATUS_ARR;
        $sectionName = ($request->section === null) ? 'general' : $request->section;

        return view("settings.$sectionName", compact('currencies', 'settings', 'statusArr', 'sectionName'));
    }

    public function update(UpdateSettingRequest $request)
    {
        $this->settingRepository->updateSetting($request->all());

        Flash::success(__('messages.settings').' '.__('messages.common.updated_successfully'));

        return redirect(route('settings.edit'));
    }

    public function updateReception(Request $request)
    {
        $input = $request->validate([
            'patient_required_fields' => 'nullable|array',
            'patient_required_fields.*' => Rule::in(Patient::REQUIRABLE_FIELDS),
            'patient_duplicate_check' => ['required', Rule::in(Patient::DUPLICATE_MODES)],
            'patient_duplicate_fields' => 'nullable|array',
            'patient_duplicate_fields.*' => Rule::in(Patient::DUPLICATE_FIELDS),
            'queue_card_count' => 'required|integer|min:0|max:9999',
            'id_scan_document_type' => ['nullable', 'integer', Rule::in(array_keys(\App\Models\MasterListItem::labels('id_document_type')))],
            'id_scan_save_as' => 'nullable|integer|exists:document_types,id',
            'default_payer' => 'nullable|in:self,policy',
            'reception_max_discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $values = [
            'patient_required_fields' => implode(',', $input['patient_required_fields'] ?? []),
            'patient_portal_login' => $request->boolean('patient_portal_login') ? '1' : '0',
            'patient_duplicate_check' => $input['patient_duplicate_check'],
            'patient_duplicate_fields' => implode(',', $input['patient_duplicate_fields'] ?? []),
            'reception_webcam' => $request->boolean('reception_webcam') ? '1' : '0',
            'queue_card_count' => (string) $input['queue_card_count'],
            'reception_id_scan' => $request->boolean('reception_id_scan') ? '1' : '0',
            'reception_mrz_scanner' => $request->boolean('reception_mrz_scanner') ? '1' : '0',
            'id_scan_document_type' => (string) ($input['id_scan_document_type'] ?? ''),
            'id_scan_save_as' => (string) ($input['id_scan_save_as'] ?? ''),
            'default_payer' => $input['default_payer'] ?? 'self',
            'reception_max_discount' => (string) ($input['reception_max_discount'] ?? 0),
        ];
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Patient::flushRegistrationSettings();

        Flash::success(__('messages.settings').' '.__('messages.common.updated_successfully'));

        return redirect(route('settings.edit', ['section' => 'reception']));
    }

    /** Settings > Inpatient: discharge rules and the discharge summary template. */
    public function updateInpatient(Request $request)
    {
        $input = $request->validate([
            'discharge_summary_template' => 'nullable|string|max:20000',
            'mar_dose_times' => 'nullable|array',
            'mar_dose_times.*' => ['nullable', 'string', 'max:100', 'regex:/^\s*([01]\d|2[0-3]):[0-5]\d(\s*,\s*([01]\d|2[0-3]):[0-5]\d)*\s*$/'],
        ]);
        $doseTimes = collect($input['mar_dose_times'] ?? [])->map(fn ($t) => preg_replace('/\s+/', '', (string) $t))->filter()->all();
        $values = [
            'mar_dose_times' => json_encode((object) $doseTimes),
            'discharge_block_unpaid' => $request->boolean('discharge_block_unpaid') ? '1' : '0',
            'discharge_require_checklist' => $request->boolean('discharge_require_checklist') ? '1' : '0',
            'discharge_summary_template' => (string) ($input['discharge_summary_template'] ?? ''),
        ];
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Flash::success(__('messages.settings').' '.__('messages.common.updated_successfully'));

        return redirect(route('settings.edit', ['section' => 'inpatient']));
    }

    /** Vital signs measured by nurses: name, unit and normal range; a type in use is deactivated, never deleted. */
    public function updateVitalTypes(Request $request)
    {
        $rules = ['name' => 'required|string|max:100', 'unit' => 'nullable|string|max:30', 'normal_min' => 'nullable|numeric',
            'normal_max' => 'nullable|numeric', 'sort_order' => 'nullable|integer|min:0|max:9999'];
        foreach ((array) $request->input('types', []) as $id => $row) {
            $type = \App\Models\VitalType::findOrFail($id);
            $data = \Illuminate\Support\Facades\Validator::make($row, $rules)->validate();
            if ($data['name'] === $type->label) {
                unset($data['name']);
            } else {
                $data['lang_key'] = null;
            }
            $type->update($data + ['is_active' => ! empty($row['is_active'])]);
        }
        $new = (array) $request->input('new', []);
        if (! empty($new['name'])) {
            \App\Models\VitalType::create(\Illuminate\Support\Facades\Validator::make($new, $rules)->validate() + ['is_active' => true]);
        }
        Flash::success(__('messages.settings').' '.__('messages.common.updated_successfully'));

        return redirect(route('settings.edit', ['section' => 'inpatient']));
    }

    public function getModule(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of((new ModuleDataTable())->get($request->only(['status'])))->make(true);
        }
    }

    public function activeDeactiveStatus(Module $module)
    {
        $is_active = ! $module->is_active;
        $module->update(['is_active' => $is_active]);

        return $this->sendSuccess(__('messages.common.status_updated_successfully'));
    }

    public function patientQueueThemeView()
    {
        $setting = getSettingValue();

        if (!isset($setting['patient_queue_theme'])) {
            $setting['patient_queue_theme'] = 0;
        }

        return view('settings.queue_theme', compact('setting'));
    }

    public function patientQueueThemeUpdate(Request $request)
    {
        $request->validate([
            'patient_queue_theme' => 'required',
        ]);

        $setting = Setting::where('key', '=', 'patient_queue_theme')->first();

        if ($setting) {
            $setting->update(['value' => $request->patient_queue_theme]);
        } else {
            Setting::create([
                'key' => 'patient_queue_theme',
                'value' => $request->patient_queue_theme,
            ]);
        }

        return $this->sendSuccess(__('messages.settings') . ' ' . __('messages.common.updated_successfully'));
    }

    public function settingsUploadThemeVideo(Request $request)
    {
        $request->validate([
            'patient_queue_theme_video' => 'required|file|mimes:mp4,webm,mov|max:20480',
        ]);

        if ($request->hasFile('patient_queue_theme_video')) {
            $setting = Setting::firstOrCreate(
                ['key' => 'patient_queue_theme_video']
            );
            $setting->clearMediaCollection(Setting::VIDEO_MEDIA_COLLECTION);
            $setting->addMedia($request->file('patient_queue_theme_video'))
                ->toMediaCollection(Setting::VIDEO_MEDIA_COLLECTION, config('app.media_disc'));
            $setting = $setting->refresh();

            $setting->update([
                'value' => $setting->getFirstMediaUrl(Setting::VIDEO_MEDIA_COLLECTION),
            ]);

        }

        return $this->sendSuccess(__('messages.settings') . ' ' . __('messages.common.updated_successfully'));
    }
}
