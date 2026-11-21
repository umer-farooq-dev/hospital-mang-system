<?php

namespace App\Repositories;

use App\Models\CashCounter;
use App\Models\CashSession;
use App\Models\FundTransfer;
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
        // 'all' = cumulative journal of every register ("Cumul des brouillards").
        $counterId = ($input['counter'] ?? null) === 'all' ? 'all'
            : ((int) ($input['counter'] ?? 0) ?: ($user->cash_counter_id ?: CashCounter::orderBy('code')->value('id')));
        $session = $counterId !== 'all' && filled($input['session'] ?? null) ? CashSession::where('cash_counter_id', $counterId)->find((int) $input['session']) : null;

        return ['from' => $from, 'to' => $to, 'counter' => $counterId, 'session' => $session];
    }

    /**
     * @return array{opening: float, rows: Collection, totals: array, closing: float}
     */
    public function journal(array $filters): array
    {
        $scope = function ($query) use ($filters) {
            $query->when($filters['counter'] !== 'all', fn ($q) => $q->where('cash_counter_id', $filters['counter']));
            $filters['session']
                ? $query->where('cash_session_id', $filters['session']->id)
                : $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        };
        $receipts = Receipt::with('patient.patientUser', 'user')->whereNull('voided_at')->tap($scope)->get();
        $refunds = Refund::with('receipt.patient.patientUser', 'user')->tap($scope)->get();
        $transferScope = function ($query, string $side) use ($filters) {
            $query->when($filters['counter'] !== 'all', fn ($q) => $q->where($side.'_counter_id', $filters['counter']));
            $filters['session']
                ? $query->where($side.'_session_id', $filters['session']->id)
                : $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        };
        $out = FundTransfer::with('toCounter', 'user')->tap(fn ($q) => $transferScope($q, 'from'))->get();
        $in = FundTransfer::with('fromCounter', 'user')->tap(fn ($q) => $transferScope($q, 'to'))->get();
        if ($filters['counter'] === 'all') {
            $out = $out->whereNull('to_counter_id');
            $in = collect();
        }

        $rows = $receipts->map(fn ($r) => [
            'at' => $r->created_at, 'patient' => $r->patient, 'label' => __('messages.cash_journal.customer_payment'),
            'credit' => true, 'amount' => $r->amount, 'receipt_number' => $r->receipt_number, 'operator' => $r->user?->full_name,
            'method' => $r->payment_method,
        ])->concat($refunds->map(fn ($f) => [
            'at' => $f->created_at, 'patient' => $f->receipt->patient, 'label' => __('messages.cash_journal.refund').' · '.$f->reason_name,
            'credit' => false, 'amount' => $f->amount, 'receipt_number' => $f->receipt->receipt_number, 'operator' => $f->user?->full_name,
            'method' => $f->payment_method,
        ]))->concat($out->map(fn ($t) => [
            'at' => $t->created_at, 'patient' => null, 'label' => __('messages.fund_transfer.out', ['to' => $t->destination_name]),
            'credit' => false, 'amount' => $t->amount, 'receipt_number' => null, 'operator' => $t->user?->full_name, 'method' => $t->payment_method,
        ]))->concat($in->map(fn ($t) => [
            'at' => $t->created_at, 'patient' => null, 'label' => __('messages.fund_transfer.in', ['from' => $t->fromCounter->code.' · '.$t->fromCounter->name]),
            'credit' => true, 'amount' => $t->amount, 'receipt_number' => null, 'operator' => $t->user?->full_name, 'method' => $t->payment_method,
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
    private function balanceBefore($counterId, Carbon $date): float
    {
        $one = fn ($q, $column) => $q->when($counterId !== 'all', fn ($q) => $q->where($column, $counterId))->where('created_at', '<', $date);
        $in = Receipt::whereNull('voided_at')->tap(fn ($q) => $one($q, 'cash_counter_id'))->sum('amount');
        $out = Refund::tap(fn ($q) => $one($q, 'cash_counter_id'))->sum('amount');
        if ($counterId !== 'all') { // between registers, transfers cancel out in the cumulative journal
            $out += FundTransfer::tap(fn ($q) => $one($q, 'from_counter_id'))->sum('amount');
            $in += FundTransfer::tap(fn ($q) => $one($q, 'to_counter_id'))->sum('amount');
        }

        return round((float) $in - (float) $out, 2);
    }
}
