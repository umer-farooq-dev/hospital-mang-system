<?php

namespace App\Repositories;

use App\Models\CashCounter;
use App\Models\CashSession;
use App\Models\Receipt;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cash journal ("Brouillard de caisse"): every movement of one register with a running
 * balance, filtered by period or by closing number ("N° Arrêt").
 */
class CashJournalRepository
{
    public function filters(array $input, User $user): array
    {
        $date = function ($value, Carbon $default) {
            try {
                return $value ? Carbon::parse($value) : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        };
        $from = $date($input['from'] ?? null, today())->startOfDay();
        $to = $date($input['to'] ?? null, today())->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $counterId = (int) ($input['counter'] ?? 0) ?: ($user->cash_counter_id ?: CashCounter::orderBy('code')->value('id'));
        $session = filled($input['session'] ?? null) ? CashSession::where('cash_counter_id', $counterId)->find((int) $input['session']) : null;

        return ['from' => $from, 'to' => $to, 'counter' => $counterId, 'session' => $session];
    }

    /**
     * @return array{opening: float, rows: Collection, totals: array, closing: float}
     */
    public function journal(array $filters): array
    {
        $scope = function ($query) use ($filters) {
            $query->where('cash_counter_id', $filters['counter']);
            $filters['session']
                ? $query->where('cash_session_id', $filters['session']->id)
                : $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        };
        $receipts = Receipt::with('patient.patientUser', 'user')->whereNull('voided_at')->tap($scope)->get();
        $refunds = Refund::with('receipt.patient.patientUser', 'user')->tap($scope)->get();

        $rows = $receipts->map(fn ($r) => [
            'at' => $r->created_at, 'patient' => $r->patient, 'label' => __('messages.cash_journal.customer_payment'),
            'credit' => true, 'amount' => $r->amount, 'receipt_number' => $r->receipt_number, 'operator' => $r->user?->full_name,
            'method' => $r->payment_method,
        ])->concat($refunds->map(fn ($f) => [
            'at' => $f->created_at, 'patient' => $f->receipt->patient, 'label' => __('messages.cash_journal.refund').' · '.$f->reason_name,
            'credit' => false, 'amount' => $f->amount, 'receipt_number' => $f->receipt->receipt_number, 'operator' => $f->user?->full_name,
            'method' => $f->payment_method,
        ]))->sortBy('at')->values();

        $opening = $filters['session'] ? $filters['session']->opening_float : $this->balanceBefore($filters['counter'], $filters['from']);
        $balance = $opening;
        $totals = [];
        $rows = $rows->map(function ($row) use (&$balance, &$totals) {
            $signed = $row['credit'] ? $row['amount'] : -$row['amount'];
            $row['initial'] = $balance;
            $balance = round($balance + $signed, 2);
            $row['balance'] = $balance;
            $totals[$row['method']] = ($totals[$row['method']] ?? 0) + $signed;

            return $row;
        });

        return ['opening' => $opening, 'rows' => $rows, 'totals' => $totals, 'closing' => $balance];
    }

    /** Net movements of the register before a date (receipts in, refunds out). */
    private function balanceBefore(int $counterId, Carbon $date): float
    {
        $in = Receipt::where('cash_counter_id', $counterId)->whereNull('voided_at')->where('created_at', '<', $date)->sum('amount');
        $out = Refund::where('cash_counter_id', $counterId)->where('created_at', '<', $date)->sum('amount');

        return round((float) $in - (float) $out, 2);
    }
}
