<?php

namespace App\Http\Controllers;

use App\Models\ImagingReportTemplate;
use App\Models\MasterListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

/** Imaging report templates (admin). */
class ImagingReportTemplateController extends AppBaseController
{
    public function index()
    {
        return view('imaging_report_templates.index', [
            'templates' => ImagingReportTemplate::orderBy('sort_order')->orderBy('id')->get(),
            'modalities' => MasterListItem::options('imaging_modality'),
        ]);
    }

    public function update(Request $request)
    {
        $rules = ['name' => 'required|string|max:150', 'body' => 'required|string|max:50000', 'modality' => 'nullable|integer', 'sort_order' => 'nullable|integer|min:0|max:9999'];
        foreach ((array) $request->input('templates', []) as $id => $row) {
            $template = ImagingReportTemplate::findOrFail($id);
            $data = Validator::make($row, $rules)->validate();
            $body = str_replace("\r\n", "\n", $data['body']);
            $changes = ['modality' => $data['modality'] ?? null, 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => ! empty($row['is_active'])];
            if ($data['name'] !== $template->label || $body !== $template->text) {
                $changes += ['name' => $data['name'], 'body' => $body, 'lang_key' => null];
            }
            $template->update($changes);
        }
        $new = (array) $request->input('new', []);
        if (! empty($new['name'])) {
            ImagingReportTemplate::create(Validator::make($new, $rules)->validate() + ['is_active' => true]);
        }
        Flash::success(__('messages.imaging.templates_saved'));

        return redirect()->route('imaging-report-templates.index');
    }
}
