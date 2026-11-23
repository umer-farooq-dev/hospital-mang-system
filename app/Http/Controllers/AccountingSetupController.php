<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Journal;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

/** Chart of accounts, fiscal years, journals and the lock date (admin / accountant). */
class AccountingSetupController extends AppBaseController
{
    const CLASSES = ['asset', 'liability', 'equity', 'income', 'expense'];

    public function index()
    {
        return view('accounting.setup', [
            'accounts' => Account::orderByRaw('code IS NULL')->orderBy('code')->orderBy('name')->get(),
            'years' => FiscalYear::orderByDesc('starts_on')->get(),
            'journals' => Journal::orderBy('code')->get(),
            'lockDate' => Setting::where('key', 'accounting_lock_date')->value('value'),
        ]);
    }

    public function updateAccounts(Request $request)
    {
        $rules = fn (?int $id) => ['code' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z.]+$/', Rule::unique('accounts', 'code')->ignore($id)],
            'name' => 'required|string|max:160', 'account_class' => ['required', Rule::in(self::CLASSES)], 'type' => 'required|in:1,2',
            'parent_id' => 'nullable|integer|exists:accounts,id'];
        foreach ((array) $request->input('accounts', []) as $id => $row) {
            $data = Validator::make((array) $row, $rules((int) $id))->validate();
            Account::findOrFail($id)->forceFill($data + ['is_postable' => ! empty($row['is_postable']), 'status' => ! empty($row['status']) ? 1 : 0])->save();
        }
        $new = (array) $request->input('new', []);
        if (trim((string) ($new['code'] ?? '')) !== '') {
            (new Account())->forceFill(Validator::make($new, $rules(null))->validate() + ['is_postable' => ! empty($new['is_postable']), 'status' => 1])->save();
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('accounting.setup');
    }

    public function updateYears(Request $request)
    {
        $rules = ['name' => 'required|string|max:60', 'starts_on' => 'required|date', 'ends_on' => 'required|date|after:starts_on', 'status' => ['required', Rule::in(FiscalYear::STATUSES)]];
        foreach ((array) $request->input('years', []) as $id => $row) {
            $year = FiscalYear::findOrFail($id);
            $data = Validator::make((array) $row, $rules)->validate();
            if ($year->status === 'locked') {
                continue; // locked for good
            }
            $year->update($data);
        }
        $new = (array) $request->input('new', []);
        if (trim((string) ($new['name'] ?? '')) !== '') {
            $data = Validator::make($new + ['status' => 'open'], $rules)->validate();
            $overlap = FiscalYear::whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on'])->exists();
            if ($overlap) {
                return back()->withErrors(['new' => __('messages.accounting.year_overlap')]);
            }
            FiscalYear::create($data);
        }
        $request->validate(['accounting_lock_date' => 'nullable|date']);
        Setting::updateOrCreate(['key' => 'accounting_lock_date'], ['value' => (string) $request->input('accounting_lock_date', '')]);
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('accounting.setup');
    }

    public function updateJournals(Request $request)
    {
        $rules = fn (?int $id) => ['code' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z]+$/', Rule::unique('journals', 'code')->ignore($id)],
            'name' => 'required|string|max:100', 'kind' => ['required', Rule::in(Journal::KINDS)]];
        foreach ((array) $request->input('journals', []) as $id => $row) {
            Journal::findOrFail($id)->update(Validator::make((array) $row, $rules((int) $id))->validate() + ['is_active' => ! empty($row['is_active'])]);
        }
        $new = (array) $request->input('new', []);
        if (trim((string) ($new['code'] ?? '')) !== '') {
            Journal::create(Validator::make($new, $rules(null))->validate() + ['is_active' => true]);
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('accounting.setup');
    }
}
