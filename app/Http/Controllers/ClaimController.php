<?php

namespace App\Http\Controllers;

use App\Models\ClaimInvoice;
use App\Models\Insurance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Claim invoices to insurers and other third-party payers. */
class ClaimController extends AppBaseController
{
    public function index(Request $request)
    {
        $insurances = Insurance::orderBy('name')->pluck('name', 'id')->all();
        $status = in_array($request->input('status'), ClaimInvoice::STATUSES, true) ? $request->input('status') : null;
        $invoices = ClaimInvoice::with('insurance', 'lines')
            ->when($request->integer('insurance_id'), fn ($q, $id) => $q->where('insurance_id', $id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(request()->attributes->get('exporting') ? 100000 : 30)->withQueryString();

        return view('claims.index', [
            'invoices' => $invoices, 'insurances' => $insurances, 'status' => $status,
            'settings' => Setting::whereIn('key', ['claim_invoice_format', 'claim_invoice_digits', 'proforma_validity_days'])->pluck('value', 'key'),
        ]);
    }

    /** What would be claimed for an insurer and a period, before making the invoices. */
    public function create(Request $request)
    {
        $data = $request->validate(['insurance_id' => 'required|exists:insurances,id', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        $insurance = Insurance::findOrFail($data['insurance_id']);

        $lines = ClaimInvoice::eligible($insurance->id, Carbon::parse($data['from']), Carbon::parse($data['to']));

        return view('claims.create', [
            'insurance' => $insurance,
            'from' => Carbon::parse($data['from']),
            'to' => Carbon::parse($data['to']),
            'lines' => $lines,
            'patients' => \App\Models\Patient::with('patientUser')->whereIn('id', $lines->pluck('patient_id')->unique())->get()->keyBy('id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['insurance_id' => 'required|exists:insurances,id', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:from',
            'note' => 'nullable|string|max:500']);
        $invoices = ClaimInvoice::generate((int) $data['insurance_id'], Carbon::parse($data['from']), Carbon::parse($data['to']), getLoggedInUserId(), $data['note'] ?? null);
        if ($invoices->isEmpty()) {
            Flash::error(__('messages.claims.nothing'));

            return redirect()->route('claims.index');
        }
        Flash::success(__('messages.claims.made', ['count' => $invoices->count(), 'total' => formatMoney($invoices->sum('total_claimed'))]));

        return $invoices->count() === 1 ? redirect()->route('claims.show', $invoices->first()->id) : redirect()->route('claims.index', ['insurance_id' => $data['insurance_id']]);
    }

    public function show(ClaimInvoice $claimInvoice)
    {
        $claimInvoice->load('insurance', 'lines.patient.patientUser', 'author', 'payments');

        return view('claims.show', ['invoice' => $claimInvoice]);
    }

    public function cancel(ClaimInvoice $claimInvoice)
    {
        try {
            $claimInvoice->cancel(getLoggedInUserId());
            Flash::success(__('messages.claims.cancelled'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('claims.show', $claimInvoice->id);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate(['claim_invoice_format' => ['required', 'string', 'max:60', 'regex:/\{seq\}/'], 'claim_invoice_digits' => 'required|integer|min:1|max:8',
            'proforma_validity_days' => 'required|integer|min:0|max:3650']);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('claims.index');
    }
}
