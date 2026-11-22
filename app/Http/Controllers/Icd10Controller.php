<?php

namespace App\Http\Controllers;

use App\Models\Icd10Code;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

/** ICD-10 list: search, add, edit, (de)activate and import from a CSV file. */
class Icd10Controller extends AppBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $codes = Icd10Code::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', Icd10Code::normalize($search) . '%')->orWhere('title', 'like', '%' . $search . '%')))
            ->orderBy('code')->paginate(50)->withQueryString();

        return view('icd10.index', ['codes' => $codes, 'search' => $search, 'total' => Icd10Code::count()]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Icd10Code::normalize((string) $request->input('code'))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z]\d{2}(\.[0-9A-Z]{1,4})?[+*]?$/', Rule::unique('icd10_codes', 'code')],
            'title' => 'required|string|max:255',
        ]);
        Icd10Code::create($data + ['is_active' => true]);
        Flash::success(__('messages.icd10.saved'));

        return redirect()->route('icd10.index', ['search' => $data['code']]);
    }

    public function update(Request $request, Icd10Code $icd10Code)
    {
        $data = $request->validate(['title' => 'required|string|max:255']);
        $icd10Code->update($data + ['is_active' => $request->boolean('is_active')]);
        Flash::success(__('messages.icd10.saved'));

        return redirect()->back();
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:20480']);
        $rows = Icd10Code::parse((string) file_get_contents($request->file('file')->getRealPath()));
        if (! $rows) {
            Flash::error(__('messages.icd10.import_empty'));

            return redirect()->route('icd10.index');
        }
        $existing = Icd10Code::whereIn('code', array_keys($rows))->pluck('code')->flip();
        $now = now();
        foreach (array_chunk($rows, 500, true) as $chunk) {
            $records = [];
            foreach ($chunk as $code => $title) {
                $records[] = ['code' => $code, 'title' => $title, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            Icd10Code::upsert($records, ['code'], ['title', 'updated_at']);
        }
        $added = count(array_diff_key($rows, $existing->all()));
        Flash::success(__('messages.icd10.imported', ['added' => $added, 'updated' => count($rows) - $added]));

        return redirect()->route('icd10.index');
    }

    /** Codes for the diagnosis forms (select2). */
    public function getSearch(Request $request)
    {
        $term = trim((string) $request->input('q'));
        $codes = Icd10Code::where('is_active', true)
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', Icd10Code::normalize($term) . '%')->orWhere('title', 'like', '%' . $term . '%')))
            ->orderBy('code')->limit(30)->get();

        return response()->json(['results' => $codes->map(fn ($c) => ['id' => $c->code, 'text' => $c->label])]);
    }
}
