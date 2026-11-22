<?php

namespace App\Http\Controllers;

use App\Models\MasterListItem;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

/** Stores (admin): where stock is kept, who may use each one, and the stock rules. */
class StoreController extends AppBaseController
{
    const SETTINGS = ['stock_fefo', 'stock_allow_negative', 'stock_sell_expired', 'stock_expiry_alert_days', 'pharmacy_default_store',
        'stock_transfer_approval', 'stock_transfer_prefix', 'stock_count_blind', 'stock_count_prefix', 'purchase_order_prefix', 'pharmacy_charge_category', 'inventory_default_store'];

    public function index()
    {
        return view('stores.index', [
            'stores' => Store::with('users:id')->orderBy('sort_order')->orderBy('name')->get(),
            'types' => MasterListItem::options('store_type'),
            'users' => User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Patient'))->orderBy('first_name')->get()
                ->mapWithKeys(fn ($u) => [$u->id => $u->full_name])->all(),
            'settings' => Setting::whereIn('key', self::SETTINGS)->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        $rules = ['name' => 'required|string|max:150', 'code' => 'nullable|string|max:30', 'store_type' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0|max:9999', 'users' => 'nullable|array', 'users.*' => 'integer|exists:users,id'];
        $save = function (Store $store, array $row) use ($rules) {
            $data = Validator::make($row, $rules)->validate();
            $store->fill(['name' => $data['name'], 'code' => $data['code'] ?? null, 'store_type' => $data['store_type'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0), 'can_sell' => ! empty($row['can_sell']),
                'is_active' => $store->exists ? ! empty($row['is_active']) : true])->save();
            $store->users()->sync($data['users'] ?? []);
        };
        foreach ((array) $request->input('stores', []) as $id => $row) {
            $save(Store::findOrFail($id), (array) $row);
        }
        $new = (array) $request->input('new', []);
        if (trim((string) ($new['name'] ?? '')) !== '') {
            $save(new Store(), $new);
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('stores.index');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'stock_expiry_alert_days' => 'required|integer|min:0|max:3650',
            'pharmacy_default_store' => ['required', Rule::exists('stores', 'id')->where('is_active', true)],
            'inventory_default_store' => ['required', Rule::exists('stores', 'id')->where('is_active', true)],
            'stock_transfer_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'stock_count_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'purchase_order_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'pharmacy_charge_category' => ['nullable', 'integer', 'exists:charge_categories,id'],
        ]);
        $values = $data + [
            'stock_fefo' => $request->boolean('stock_fefo') ? '1' : '0',
            'stock_allow_negative' => $request->boolean('stock_allow_negative') ? '1' : '0',
            'stock_sell_expired' => $request->boolean('stock_sell_expired') ? '1' : '0',
            'stock_transfer_approval' => $request->boolean('stock_transfer_approval') ? '1' : '0',
            'stock_count_blind' => $request->boolean('stock_count_blind') ? '1' : '0',
        ];
        $values['pharmacy_charge_category'] = (string) ($data['pharmacy_charge_category'] ?? '');
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('stores.index');
    }
}
