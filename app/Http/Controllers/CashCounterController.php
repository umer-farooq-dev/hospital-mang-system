<?php

namespace App\Http\Controllers;

use App\Models\CashCounter;
use App\Models\Setting;
use App\Models\User;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Cash registers, the users assigned to each one, and the receipt settings.
 */
class CashCounterController extends AppBaseController
{
    const SETTINGS = ['receipt_start_number', 'receipt_followup_days', 'receipt_paper', 'cash_payment_method', 'receipt_footer_text',
        'followup_mode', 'followup_percent', 'policy_expired_mode', 'cashier_session_required'];

    const FOLLOWUP_MODES = ['off', 'free', 'percent'];

    const POLICY_EXPIRED_MODES = ['self_pay', 'block'];

    const PAPER_SIZES = ['a5', 'a4', 'thermal'];

    public function index()
    {
        $counters = CashCounter::orderBy('code')->get();
        // Staff only: patients never collect money.
        $users = User::where('owner_type', '!=', \App\Models\Patient::class)->orWhereNull('owner_type')
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'cash_counter_id']);
        $settings = Setting::whereIn('key', self::SETTINGS)->pluck('value', 'key');

        return view('cash_counters.index', compact('counters', 'users', 'settings'));
    }

    public function update(Request $request)
    {
        $rows = (array) $request->input('counters', []);
        $new = $request->input('new');
        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'account_cash' => 'nullable|string|max:30',
            'account_cheque' => 'nullable|string|max:30',
            'account_electronic' => 'nullable|string|max:30',
        ];

        DB::transaction(function () use ($rows, $new, $rules, $request) {
            foreach ($rows as $id => $row) {
                $counter = CashCounter::findOrFail($id);
                $data = validator($row, $rules + ['code' => ['required', 'max:20', Rule::unique('cash_counters', 'code')->ignore($counter->id)]])->validate();
                $counter->update($data + ['is_reception' => ! empty($row['is_reception']), 'is_active' => ! empty($row['is_active'])]);
            }
            if (! empty($new['code']) || ! empty($new['name'])) {
                $data = validator($new, $rules + ['code' => 'required|max:20|unique:cash_counters,code'])->validate();
                CashCounter::create($data + ['is_reception' => ! empty($new['is_reception']), 'is_active' => true]);
            }

            $counterIds = CashCounter::pluck('id')->all();
            foreach ((array) $request->input('user_counter', []) as $userId => $counterId) {
                $counterId = $counterId === '' || $counterId === null ? null : (int) $counterId;
                if ($counterId !== null && ! in_array($counterId, $counterIds)) {
                    throw ValidationException::withMessages(['user_counter' => __('messages.cashier.unknown_counter')]);
                }
                User::whereKey((int) $userId)->update(['cash_counter_id' => $counterId]);
            }

            $settings = validator($request->only(self::SETTINGS), [
                'receipt_start_number' => 'required|integer|min:1',
                'receipt_followup_days' => 'required|integer|min:0|max:365',
                'receipt_paper' => ['required', Rule::in(self::PAPER_SIZES)],
                'cash_payment_method' => 'required|integer',
                'receipt_footer_text' => 'nullable|string|max:500',
                'followup_mode' => ['required', Rule::in(self::FOLLOWUP_MODES)],
                'followup_percent' => 'nullable|numeric|min:0|max:100',
                'policy_expired_mode' => ['required', Rule::in(self::POLICY_EXPIRED_MODES)],
                'cashier_session_required' => 'nullable|in:0,1',
            ])->validate();
            foreach (self::SETTINGS as $key) {
                $value = $settings[$key] ?? null;
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        });

        Flash::success(__('messages.cashier.counters').' '.__('messages.common.updated_successfully'));

        return redirect(route('cash-counters.index'));
    }
}
