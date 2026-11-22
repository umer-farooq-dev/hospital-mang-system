<?php

namespace App\Http\Controllers;

use App\Models\LabImportProfile;
use App\Models\PathologyParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

/** Import profiles for analyser files (admin). */
class LabImportProfileController extends AppBaseController
{
    public function index()
    {
        return view('lab_import_profiles.index', [
            'profiles' => LabImportProfile::orderBy('name')->get(),
            'parameters' => PathologyParameter::orderBy('parameter_name')->pluck('parameter_name', 'id')->all(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = ['name' => 'required|string|max:150', 'header_row' => 'required|integer|min:1|max:100', 'sample_column' => 'required|string|max:100'];
        $mappings = function (array $row): array {
            $out = [];
            foreach ((array) ($row['columns'] ?? []) as $i => $title) {
                $parameterId = (int) ($row['parameters'][$i] ?? 0);
                if (trim((string) $title) !== '' && $parameterId > 0) {
                    $out[trim($title)] = $parameterId;
                }
            }

            return $out;
        };
        foreach ((array) $request->input('profiles', []) as $id => $row) {
            $data = Validator::make($row, $rules)->validate();
            LabImportProfile::findOrFail($id)->update($data + ['mappings' => $mappings($row), 'is_active' => ! empty($row['is_active'])]);
        }
        $new = (array) $request->input('new', []);
        if (! empty($new['name'])) {
            LabImportProfile::create(Validator::make($new, $rules)->validate() + ['mappings' => $mappings($new), 'is_active' => true]);
        }
        Flash::success(__('messages.lab_import.saved'));

        return redirect()->route('lab-import-profiles.index');
    }
}
