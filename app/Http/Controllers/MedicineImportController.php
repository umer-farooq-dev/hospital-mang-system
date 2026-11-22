<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\LabImportProfile;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

/** Import the medicine reference list (old "Table DCI": CIS code, common name, form, route) from CSV or Excel. */
class MedicineImportController extends AppBaseController
{
    public function create()
    {
        return view('medicines.import', [
            'categories' => Category::orderBy('name')->pluck('name', 'id')->all(),
            'brands' => Brand::orderBy('name')->pluck('name', 'id')->all(),
            'columns' => array_keys(Medicine::IMPORT_COLUMNS),
            'summary' => session('medicine_import'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
        ]);
        $rows = LabImportProfile::readRows($request->file('file'));
        $summary = Medicine::importRows($rows, (int) $data['category_id'], (int) $data['brand_id']);
        Flash::success(__('messages.medicine_import.done', ['created' => $summary['created'], 'updated' => $summary['updated'], 'skipped' => $summary['skipped']]));

        return redirect()->route('medicines.import')->with('medicine_import', $summary);
    }
}
