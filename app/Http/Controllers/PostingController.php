<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\MasterListItem;
use App\Models\Setting;
use App\Utils\Posting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;

/** Posting rules (accounts and journals per event) and the automatic entries. */
class PostingController extends AppBaseController
{
    public function index()
    {
        $codes = \App\Models\Account::pluck('id', 'code');

        return view('accounting.posting', [
            'keys' => Posting::accountKeys(),
            'map' => Posting::accounts(),
            'defaults' => collect(Posting::accountKeys())->mapWithKeys(fn ($label, $key) => [$key => $codes[Posting::defaultCode($key)] ?? null])->all(),
            'journalMap' => Posting::journals(),
            'journalKeys' => collect(array_keys(Posting::JOURNALS))->mapWithKeys(fn ($k) => [$k => __('messages.posting.journal_keys.'.$k)])->all()
                + collect(MasterListItem::labels('payment_method'))->mapWithKeys(fn ($label, $code) => ['method.'.$code => __('messages.posting.receipts_by', ['name' => $label])])->all(),
            'journalDefaults' => collect(Posting::JOURNALS)->map(fn ($code) => Journal::where('code', $code)->value('id'))->all(),
            'accounts' => JournalEntryController::postableAccounts(),
            'journals' => Journal::where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn ($j) => [$j->id => $j->code.' · '.$j->name])->all(),
            'summary' => session('posting_summary'),
        ]);
    }

    public function updateRules(Request $request)
    {
        $request->validate(['accounts' => 'nullable|array', 'accounts.*' => 'nullable|integer|exists:accounts,id',
            'journals' => 'nullable|array', 'journals.*' => 'nullable|integer|exists:journals,id']);
        $keep = fn (array $values, array $allowed) => collect($values)->only($allowed)->filter()->map(fn ($v) => (int) $v)->all();
        Setting::updateOrCreate(['key' => 'posting_accounts'], ['value' => json_encode($keep((array) $request->input('accounts', []), array_keys(Posting::accountKeys())))]);
        $journalKeys = array_merge(array_keys(Posting::JOURNALS), array_map(fn ($c) => 'method.'.$c, array_keys(MasterListItem::labels('payment_method'))));
        Setting::updateOrCreate(['key' => 'posting_journals'], ['value' => json_encode($keep((array) $request->input('journals', []), $journalKeys))]);
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('accounting.posting');
    }

    /** Post the entries missing for a period (running it twice never posts twice). */
    public function storeGeneration(Request $request)
    {
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        $result = Posting::generate(Carbon::parse($data['from']), Carbon::parse($data['to']), getLoggedInUserId());
        $result['errors'] = array_slice($result['errors'], 0, 200);
        Flash::success(__('messages.posting.generated', ['posted' => $result['posted'], 'skipped' => $result['skipped'], 'errors' => count($result['errors'])]));

        return redirect()->route('accounting.posting')->with('posting_summary', $result + $data);
    }
}
