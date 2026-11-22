<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

/** Suppliers of medicines and goods (brands stay the manufacturers). */
class SupplierController extends AppBaseController
{
    const RULES = ['name' => 'required|string|max:150', 'contact_person' => 'nullable|string|max:150', 'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:150', 'address' => 'nullable|string|max:255', 'tax_number' => 'nullable|string|max:50'];

    public function index()
    {
        return view('suppliers.index', ['suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('suppliers', []) as $id => $row) {
            Supplier::findOrFail($id)->update(Validator::make((array) $row, self::RULES)->validate() + ['is_active' => ! empty($row['is_active'])]);
        }
        $new = (array) $request->input('new', []);
        if (trim((string) ($new['name'] ?? '')) !== '') {
            Supplier::create(Validator::make($new, self::RULES)->validate() + ['is_active' => true]);
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('suppliers.index');
    }
}
