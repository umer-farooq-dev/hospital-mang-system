<?php

namespace App\Http\Controllers;

use App\Models\MasterListItem;
use App\Models\ReferrerCommission;
use App\Models\ReferrerPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

/** Referrer ("apporteur") commissions: rates, report per period, payments. */
class ReferrerCommissionController extends AppBaseController
{
    private function period(Request $request): array
    {
        return [Carbon::parse($request->input('from') ?: now()->subMonthNoOverflow()->startOfMonth()), Carbon::parse($request->input('to') ?: now()->subMonthNoOverflow()->endOfMonth())];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        return view('referrer_commissions.index', [
            'from' => $from, 'to' => $to,
            'rows' => ReferrerCommission::report($from, $to),
            'referrers' => MasterListItem::options('referrer'),
            'rates' => ReferrerCommission::pluck('percent', 'referrer_code'),
        ]);
    }

    public function updateRates(Request $request)
    {
        $request->validate(['rates' => 'nullable|array', 'rates.*' => 'nullable|numeric|min:0|max:100']);
        $codes = array_keys(MasterListItem::labels('referrer'));
        foreach ((array) $request->input('rates', []) as $code => $percent) {
            if (in_array((int) $code, $codes, true)) {
                ReferrerCommission::updateOrCreate(['referrer_code' => (int) $code], ['percent' => (float) ($percent ?? 0)]);
            }
        }
        Flash::success(__('messages.stock.saved'));

        return redirect()->route('referrer-commissions.index', $request->only('from', 'to'));
    }

    public function storePayout(Request $request)
    {
        $data = $request->validate([
            'referrer_code' => ['required', 'integer', Rule::in(array_keys(MasterListItem::labels('referrer')))],
            'from' => 'required|date', 'to' => 'required|date|after_or_equal:from',
            'amount' => 'required|numeric|min:0.01',
            'paid_on' => 'required|date|before_or_equal:today',
            'payment_method' => ['nullable', 'integer', Rule::in(array_keys(MasterListItem::options('payment_method')))],
            'reference' => 'nullable|string|max:100',
        ]);
        ReferrerPayout::create(['referrer_code' => $data['referrer_code'], 'period_from' => $data['from'], 'period_to' => $data['to'], 'amount' => $data['amount'],
            'paid_on' => $data['paid_on'], 'payment_method' => $data['payment_method'] ?? null, 'reference' => $data['reference'] ?? null, 'created_by' => getLoggedInUserId()]);
        Flash::success(__('messages.referrer_commissions.paid', ['amount' => formatMoney($data['amount'])]));

        return redirect()->route('referrer-commissions.index', ['from' => $data['from'], 'to' => $data['to']]);
    }

    public function exportReport(Request $request)
    {
        [$from, $to] = $this->period($request);
        $rows = ReferrerCommission::report($from, $to);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $t = fn ($k) => __('messages.referrer_commissions.'.$k);
            fputcsv($out, [$t('referrer'), $t('visits'), $t('amount'), '%', $t('commission'), $t('paid_column'), $t('due')], ';');
            foreach ($rows as $r) {
                fputcsv($out, [$r['name'], $r['visits'], number_format($r['amount'], 2, ',', ''), number_format($r['percent'], 2, ',', ''),
                    number_format($r['commission'], 2, ',', ''), number_format($r['paid'], 2, ',', ''), number_format($r['due'], 2, ',', '')], ';');
            }
            fclose($out);
        }, 'referrer-commissions-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
