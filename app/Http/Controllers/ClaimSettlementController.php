<?php

namespace App\Http\Controllers;

use App\Models\ClaimInvoice;
use App\Models\ClaimLine;
use App\Models\Insurance;
use App\Models\Setting;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** What payers paid and rejected, what happens to rejected lines, receivables ageing and statements. */
class ClaimSettlementController extends AppBaseController
{
    public function storeSettlement(Request $request, ClaimInvoice $claimInvoice)
    {
        $request->validate([
            'paid_on' => 'required|date|before_or_equal:today',
            'payment_method' => ['nullable', 'integer', Rule::in(array_keys(\App\Models\MasterListItem::options('payment_method')))],
            'reference' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
            'lines' => 'required|array',
            'lines.*.paid' => 'nullable|numeric|min:0',
            'lines.*.reason' => 'nullable|integer',
        ]);
        try {
            $payment = $claimInvoice->settle((array) $request->input('lines'), $request->only(['paid_on', 'payment_method', 'reference', 'note']), getLoggedInUserId());
            Flash::success($payment ? __('messages.claim_settlement.recorded', ['amount' => formatMoney($payment->amount)]) : __('messages.claim_settlement.rejections_recorded'));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('claims.show', $claimInvoice->id);
    }

    /** resubmit | write_off | to_patient on a rejected line. */
    public function updateLine(ClaimLine $claimLine, string $action)
    {
        abort_unless(isset(ClaimLine::ACTIONS[$action]), 404);
        try {
            $claimLine->settleRejected($action);
            Flash::success(__('messages.claim_settlement.line_done.'.$action));
        } catch (HttpException $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('claims.show', $claimLine->claim_invoice_id);
    }

    /** Amounts still due by payer and by age of the invoice (buckets set by the admin). */
    public function ageing()
    {
        $buckets = self::buckets();
        $today = now()->startOfDay();
        $rows = [];
        foreach (ClaimInvoice::with('insurance', 'lines')->whereIn('status', [ClaimInvoice::ISSUED, ClaimInvoice::PARTIAL])->get() as $invoice) {
            $due = $invoice->outstanding();
            if ($due <= 0) {
                continue;
            }
            $age = (int) $invoice->issued_on->diffInDays($today);
            $slot = count($buckets);
            foreach ($buckets as $i => $limit) {
                if ($age <= $limit) {
                    $slot = $i;
                    break;
                }
            }
            $rows[$invoice->insurance_id] ??= ['payer' => $invoice->insurance->name ?? '', 'id' => $invoice->insurance_id, 'slots' => array_fill(0, count($buckets) + 1, 0.0), 'total' => 0.0];
            $rows[$invoice->insurance_id]['slots'][$slot] += $due;
            $rows[$invoice->insurance_id]['total'] += $due;
        }
        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return view('claims.ageing', ['rows' => $rows, 'buckets' => $buckets, 'setting' => implode(',', $buckets)]);
    }

    public function updateAgeing(Request $request)
    {
        $data = $request->validate(['claim_ageing_buckets' => ['required', 'string', 'max:50', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/']]);
        $days = collect(explode(',', $data['claim_ageing_buckets']))->map(fn ($d) => (int) trim($d))->filter()->unique()->sort()->values()->all();
        Setting::updateOrCreate(['key' => 'claim_ageing_buckets'], ['value' => implode(',', $days)]);
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('claims.ageing');
    }

    /** Statement of one payer: invoices, payments and amounts not owed any more, with the running balance. */
    public function statement(Request $request)
    {
        $insurances = Insurance::orderBy('name')->pluck('name', 'id')->all();
        $insuranceId = (int) $request->input('insurance_id');
        $from = Carbon::parse($request->input('from') ?: now()->startOfYear())->startOfDay();
        $to = Carbon::parse($request->input('to') ?: now())->endOfDay();
        $entries = $insuranceId ? ClaimInvoice::statementEntries($insuranceId) : collect();
        $opening = round($entries->filter(fn ($e) => $e['date']->lt($from))->sum('amount'), 2);
        $entries = $entries->filter(fn ($e) => $e['date']->between($from, $to))->values();

        return view('claims.statement', compact('insurances', 'insuranceId', 'from', 'to', 'entries', 'opening'));
    }

    /** @return int[] upper limits in days, e.g. [30, 60, 90] */
    public static function buckets(): array
    {
        $days = collect(explode(',', (string) Stock::setting('claim_ageing_buckets', '30,60,90')))->map(fn ($d) => (int) trim($d))->filter()->unique()->sort()->values()->all();

        return $days ?: [30, 60, 90];
    }
}
