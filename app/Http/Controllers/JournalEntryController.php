<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Utils\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Journal entries: list, manual vouchers, reversal. */
class JournalEntryController extends AppBaseController
{
    public static function postableAccounts(): array
    {
        return Account::where('is_postable', true)->where('status', 1)->orderBy('code')->get()
            ->mapWithKeys(fn ($a) => [$a->id => trim($a->code.' '.$a->name)])->all();
    }

    public function index(Request $request)
    {
        $from = Carbon::parse($request->input('from') ?: now()->startOfMonth())->toDateString();
        $to = Carbon::parse($request->input('to') ?: now())->toDateString();
        $entries = JournalEntry::with('journal', 'lines')
            ->whereBetween('entry_date', [$from, $to])
            ->when($request->integer('journal_id'), fn ($q, $id) => $q->where('journal_id', $id))
            ->when(trim((string) $request->input('search')) !== '', fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('reference', 'like', '%'.$request->input('search').'%')->orWhere('label', 'like', '%'.$request->input('search').'%')))
            ->orderByDesc('entry_date')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('accounting.entries', ['entries' => $entries, 'journals' => Journal::orderBy('code')->pluck('name', 'id')->all(), 'from' => $from, 'to' => $to]);
    }

    public function create()
    {
        return view('accounting.entry_create', [
            'journals' => Journal::where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn ($j) => [$j->id => $j->code.' · '.$j->name])->all(),
            'accounts' => self::postableAccounts(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'journal_id' => 'required|exists:journals,id', 'entry_date' => 'required|date', 'label' => 'required|string|max:255', 'reference' => 'nullable|string|max:100',
            'lines' => 'required|array', 'lines.*.account_id' => 'nullable|integer', 'lines.*.label' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0', 'lines.*.credit' => 'nullable|numeric|min:0',
        ]);
        try {
            $entry = Ledger::post(Journal::findOrFail($data['journal_id']), $data['entry_date'], $data['label'], $data['lines'], $data['reference'] ?? null,
                ['event' => 'manual'], getLoggedInUserId());
        } catch (HttpException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }
        Flash::success(__('messages.accounting.posted', ['number' => $entry->number]));

        return redirect()->route('accounting.entries.show', $entry->id);
    }

    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load('journal', 'lines.account', 'author');

        return view('accounting.entry_show', ['entry' => $journalEntry, 'reversal' => $journalEntry->reversedBy(),
            'original' => $journalEntry->reversal_of ? JournalEntry::find($journalEntry->reversal_of) : null]);
    }

    public function storeReversal(Request $request, JournalEntry $journalEntry)
    {
        $request->validate(['entry_date' => 'nullable|date']);
        try {
            $reversal = Ledger::reverse($journalEntry->load('lines', 'journal'), $request->input('entry_date') ?: now(), getLoggedInUserId());
            Flash::success(__('messages.accounting.posted', ['number' => $reversal->number]));

            return redirect()->route('accounting.entries.show', $reversal->id);
        } catch (HttpException $e) {
            Flash::error($e->getMessage());

            return redirect()->route('accounting.entries.show', $journalEntry->id);
        }
    }
}
