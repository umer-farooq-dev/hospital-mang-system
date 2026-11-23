<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Setting;
use App\Utils\SageExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

/** Export of journal entries to the accounting software (Sage), with the layout set by the admin. */
class AccountingExportController extends AppBaseController
{
    public function index(Request $request)
    {
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth());
        $to = Carbon::parse($request->input('to') ?: now());
        $options = SageExport::options();
        $entries = SageExport::entries($from, $to, true);

        return view('accounting.export', [
            'options' => $options, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'pending' => $entries->count(), 'preview' => array_slice(SageExport::rows($entries, $options), 0, 15),
            'journals' => Journal::orderBy('code')->pluck('name', 'id')->all(),
            'exported' => JournalEntry::whereNotNull('exported_at')->count(),
        ]);
    }

    /** Download the file; with "mark" the entries are flagged exported so the next export skips them. */
    public function exportEntries(Request $request)
    {
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from', 'journal_id' => 'nullable|integer|exists:journals,id']);
        $options = SageExport::options();
        $entries = SageExport::entries(Carbon::parse($data['from']), Carbon::parse($data['to']), ! $request->boolean('again'), $data['journal_id'] ?? null);
        if ($entries->isEmpty()) {
            Flash::error(__('messages.sage.nothing'));

            return redirect()->route('accounting.export', $request->only('from', 'to'));
        }
        $content = SageExport::content(SageExport::rows($entries, $options), $options);
        if ($request->boolean('mark')) {
            JournalEntry::whereIn('id', $entries->pluck('id'))->whereNull('exported_at')->update(['exported_at' => now()]);
        }
        $name = 'ecritures-'.Carbon::parse($data['from'])->format('Ymd').'-'.Carbon::parse($data['to'])->format('Ymd').'.'.$options['extension'];

        return response($content, 200, ['Content-Type' => 'text/csv; charset='.$options['encoding'], 'Content-Disposition' => 'attachment; filename="'.$name.'"']);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'columns' => 'required|array|min:1', 'columns.*' => ['nullable', Rule::in(SageExport::COLUMNS)],
            'separator' => ['required', Rule::in(array_keys(SageExport::SEPARATORS))],
            'date_format' => ['required', Rule::in(array_keys(SageExport::DATE_FORMATS))],
            'decimal' => 'required|in:".",","',
            'encoding' => ['required', Rule::in(array_keys(SageExport::ENCODINGS))],
            'extension' => 'required|in:csv,txt',
            'group' => ['required', Rule::in(SageExport::GROUPS)],
        ]);
        $data['columns'] = array_values(array_unique(array_filter($data['columns'])));
        $data['header'] = $request->boolean('header');
        Setting::updateOrCreate(['key' => 'sage_export'], ['value' => json_encode($data)]);
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('accounting.export');
    }
}
