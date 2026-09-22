<?php

namespace App\Services;

use App\Models\PaymentDetail;
use App\Models\Sale;

class CustomerPortalStatement
{
    public function operation(Sale $sale): array
    {
        $sale->loadMissing(['lot.project', 'lot.block', 'saleLots.lot.project', 'saleLots.lot.block']);
        $lots = $sale->saleLots->sortByDesc('is_primary')->pluck('lot')->filter()->values();
        if ($lots->isEmpty() && $sale->lot) {
            $lots = collect([$sale->lot]);
        }
        return [
            'id' => $sale->id, 'code' => $sale->sale_code, 'date' => $sale->sale_date,
            'status' => $sale->status, 'project' => $lots->pluck('project.name')->filter()->unique()->implode(', '),
            'lots' => $lots->map(fn ($lot) => ['block' => $lot->block?->name, 'number' => $lot->number])->all(),
        ];
    }

    public function make(Sale $sale): array
    {
        $schedules = $sale->paymentSchedules()->orderBy('installment_number')->orderBy('id')->get();
        $payments = $sale->payments()->orderBy('payment_date')->orderBy('id')->get();
        $sale->loadMissing('lateFeeSetting');
        // Same applied-capital rule as PaymentController::getSchedules; remaining_balance
        // is not summed because at generation it represents the financing balance.
        $applied = PaymentDetail::whereIn('payment_schedule_id', $schedules->pluck('id'))
            ->whereHas('payment', fn ($q) => $q->where('sale_id', $sale->id)->where('status', 'activo'))
            ->selectRaw('payment_schedule_id, SUM(applied_amount) as paid')->groupBy('payment_schedule_id')->pluck('paid', 'payment_schedule_id');
        $rows = $schedules->map(function ($schedule) use ($applied, $sale, $payments) {
            $schedule->setRelation('sale', $sale);
            $paid = (float) ($applied[$schedule->id] ?? 0) + (float) $payments->where('status', 'activo')->where('payment_schedule_id', $schedule->id)->sum('late_fee_paid');
            $lateFee = $schedule->status === 'pagado' ? (float) $schedule->late_fee : app(PaymentScheduleLateFee::class)->calculate($schedule);
            $amount = round((float) $schedule->total_amount + $lateFee, 2);
            $balance = $schedule->status === 'pagado' ? 0 : max(0, round($amount - $paid, 2));
            return [
                'number' => (int) $schedule->installment_number + 1,
                'label' => $schedule->schedule_type === 'contado' ? 'Contado' : ((int) $schedule->installment_number === 0 ? 'Inicial' : 'Cuota'),
                'due_date' => $schedule->due_date, 'amount' => $amount, 'late_fee' => $lateFee,
                'paid' => $paid, 'balance' => $balance, 'status' => $schedule->status,
                'overdue' => $balance > 0 && $schedule->isOverdueForCollections(),
            ];
        });
        $pending = $rows->where('balance', '>', 0);
        return $this->operation($sale) + [
            'summary' => [
                'total' => (float) $sale->lot_price,
                'paid' => round((float) $payments->where('status', 'activo')->sum('amount'), 2),
                'balance' => round((float) $rows->sum('balance'), 2),
                'installments' => $rows->count(), 'paid_installments' => $rows->where('status', 'pagado')->count(),
                'pending_installments' => $pending->count(), 'next' => $pending->sortBy('due_date')->first(),
            ],
            'schedules' => $rows->values()->all(),
            'payments' => $payments->map(fn ($payment) => [
                'id' => $payment->id, 'date' => $payment->payment_date, 'amount' => (float) $payment->amount,
                'method' => $payment->payment_method, 'status' => $payment->status, 'concept' => $payment->payment_type,
            ])->all(),
        ];
    }
}
