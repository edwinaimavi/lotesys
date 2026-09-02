<?php

use App\Http\Controllers\Admin\SaleController;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

function createSaleForPaymentScheduleTest(array $attributes): Sale
{
    $now = now();

    $companyId = DB::table('companies')->insertGetId([
        'business_name' => 'Empresa de prueba',
        'trade_name' => 'Prueba',
        'ruc' => '20123456789',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $projectId = DB::table('projects')->insertGetId([
        'company_id' => $companyId,
        'name' => 'Proyecto de prueba',
        'code' => 'PROY-TEST',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $blockId = DB::table('blocks')->insertGetId([
        'project_id' => $projectId,
        'name' => 'Manzana de prueba',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $lotId = DB::table('lots')->insertGetId([
        'project_id' => $projectId,
        'block_id' => $blockId,
        'code' => 'LOTE-TEST',
        'number' => '1',
        'area' => 100,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $customerId = DB::table('customers')->insertGetId([
        'first_name' => 'Cliente',
        'last_name' => 'Prueba',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return Sale::create(array_merge([
        'customer_id' => $customerId,
        'lot_id' => $lotId,
        'sale_type' => 'financiado',
        'lot_price' => 4000,
        'initial_payment' => 1000,
        'balance_finance' => 3000,
        'installments_count' => 3,
        'payment_mode' => 'automatico',
        'monthly_payment' => 1000,
        'interest_rate' => 0,
        'payment_day' => 30,
        'status' => 'activo',
    ], $attributes));
}

it('starts normal installments one month after the initial payment date', function () {
    $sale = createSaleForPaymentScheduleTest([
        'sale_code' => 'VTA-CRONOGRAMA-TEST',
        'sale_date' => '2026-03-30',
        'lot_price' => 37000,
        'initial_payment' => 1000,
        'balance_finance' => 36000,
        'installments_count' => 36,
        'payment_mode' => 'automatico',
        'monthly_payment' => 1000,
        'interest_rate' => 0,
        'first_payment_date' => '2026-03-30',
        'payment_day' => 30,
    ]);

    $method = new ReflectionMethod(
        SaleController::class,
        'generatePaymentSchedules'
    );

    $method->invoke(new SaleController(), $sale, $sale->toArray());

    $schedules = $sale->paymentSchedules()
        ->orderBy('installment_number')
        ->get();

    expect($schedules)->toHaveCount(37)
        ->and($schedules->take(4)->map->only([
            'schedule_type',
            'installment_number',
            'due_date',
        ])->values()->all())->toBe([
            [
                'schedule_type' => 'inicial',
                'installment_number' => 0,
                'due_date' => '2026-03-30',
            ],
            [
                'schedule_type' => 'cuota',
                'installment_number' => 1,
                'due_date' => '2026-04-30',
            ],
            [
                'schedule_type' => 'cuota',
                'installment_number' => 2,
                'due_date' => '2026-05-30',
            ],
            [
                'schedule_type' => 'cuota',
                'installment_number' => 3,
                'due_date' => '2026-06-30',
            ],
        ])
        ->and((float) $schedules[0]->installment_amount)->toBe(1000.0)
        ->and((float) $schedules[1]->installment_amount)->toBe(1000.0)
        ->and((float) $schedules[1]->remaining_balance)->toBe(35000.0);
});

it('uses the last valid day when a target month is shorter', function () {
    $sale = createSaleForPaymentScheduleTest([
        'sale_code' => 'VTA-FIN-MES-TEST',
        'sale_date' => '2026-01-31',
        'first_payment_date' => '2026-01-31',
        'payment_day' => 31,
    ]);

    $method = new ReflectionMethod(
        SaleController::class,
        'generatePaymentSchedules'
    );

    $method->invoke(new SaleController(), $sale, $sale->toArray());

    expect($sale->paymentSchedules()
        ->orderBy('installment_number')
        ->pluck('due_date')
        ->all())->toBe([
            '2026-01-31',
            '2026-02-28',
            '2026-03-31',
            '2026-04-30',
        ]);
});
