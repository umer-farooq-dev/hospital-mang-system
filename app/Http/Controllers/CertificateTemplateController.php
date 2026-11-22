<?php

namespace App\Http\Controllers;

use App\Models\CertificateTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

/** Certificate templates and numbering prefix (admin). */
class CertificateTemplateController extends AppBaseController
{
    public function index()
    {
        return view('certificate_templates.index', [
            'templates' => CertificateTemplate::orderBy('sort_order')->orderBy('id')->get(),
            'prefix' => Setting::where('key', 'certificate_prefix')->value('value') ?? 'CM',
        ]);
    }

    public function update(Request $request)
    {
        $request->validate(['certificate_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/']]);
        Setting::updateOrCreate(['key' => 'certificate_prefix'], ['value' => strtoupper($request->input('certificate_prefix'))]);
        $rules = ['name' => 'required|string|max:150', 'body' => 'required|string|max:20000', 'sort_order' => 'nullable|integer|min:0|max:9999'];
        foreach ((array) $request->input('templates', []) as $id => $row) {
            $template = CertificateTemplate::findOrFail($id);
            $data = Validator::make($row, $rules)->validate();
            // Unchanged seeded text keeps its translation; edited text becomes the admin's own.
            $changes = ['sort_order' => $data['sort_order'] ?? 0, 'is_active' => ! empty($row['is_active'])];
            if ($data['name'] !== $template->label || str_replace("\r\n", "\n", $data['body']) !== $template->text) {
                $changes += ['name' => $data['name'], 'body' => str_replace("\r\n", "\n", $data['body']), 'lang_key' => null];
            }
            $template->update($changes);
        }
        $new = (array) $request->input('new', []);
        if (! empty($new['name'])) {
            $data = Validator::make($new, $rules)->validate();
            CertificateTemplate::create($data + ['is_active' => true]);
        }
        Flash::success(__('messages.certificate.templates_saved'));

        return redirect()->route('certificate-templates.index');
    }
}
