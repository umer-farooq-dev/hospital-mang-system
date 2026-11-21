<?php

namespace App\Http\Controllers;

use App\Models\CashCounter;
use App\Models\CashSession;
use App\Repositories\CashJournalRepository;
use Auth;
use Illuminate\Http\Request;

/**
 * Cash journal ("Brouillard de caisse") on screen and printed.
 */
class CashJournalController extends AppBaseController
{
    /** @var CashJournalRepository */
    private $journalRepository;

    public function __construct(CashJournalRepository $journalRepo)
    {
        $this->journalRepository = $journalRepo;
    }

    private function data(Request $request): array
    {
        $filters = $this->journalRepository->filters($request->all(), Auth::user());

        return [
            'filters' => $filters,
            'journal' => $this->journalRepository->journal($filters),
            'counters' => ['all' => __('messages.cash_journal.all_registers')] + CashCounter::orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->code.' · '.$c->name])->all(),
            'sessions' => $filters['counter'] === 'all' ? [] : CashSession::where('cash_counter_id', $filters['counter'])->whereNotNull('closing_number')
                ->latest('id')->take(100)->get()->mapWithKeys(fn ($s) => [$s->id => 'N° '.$s->closing_number.' · '.$s->closed_at->format('d/m/Y H:i')])->all(),
        ];
    }

    public function index(Request $request)
    {
        return view('cash_journal.index', $this->data($request));
    }

    public function print(Request $request)
    {
        return view('cash_journal.print', $this->data($request) + ['settings' => getSettingValue()]);
    }
}
