<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LateFeeSetting;
use App\Models\Lot;
use App\Models\Sale;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use App\Models\Holiday;

class SaleController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $customers = Customer::where('status', 1)
            ->orderBy('first_name')
            ->get();
        $lots = Lot::orderBy('code')->get();

        $lateFeeSettings = LateFeeSetting::where('status', 'activo')
            ->orderBy('grace_days')
            ->get();

        return view('admin.sales.index', compact(
            'customers',
            'lots',
            'lateFeeSettings'
        ));
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $sales = Sale::with(
            'customer',
            'lot',
            'creator',
            'updater'
        )
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($sales)

            ->addIndexColumn()

            ->addColumn('customer', function ($sale) {

                if ($sale->customer?->person_type == 'juridica') {

                    return $sale->customer->business_name ?? '—';
                }

                return trim(
                    ($sale->customer->first_name ?? '')
                        . ' '
                        . ($sale->customer->last_name ?? '')
                );
            })

            ->addColumn('lot', function ($sale) {

                return $sale->lot->code ?? '—';
            })

            ->editColumn('sale_date', function ($sale) {

                return $sale->sale_date
                    ? date('d/m/Y', strtotime($sale->sale_date))
                    : '—';
            })

            ->editColumn('lot_price', function ($sale) {

                return 'S/ ' . number_format($sale->lot_price, 2);
            })

            ->editColumn('initial_payment', function ($sale) {

                return 'S/ ' . number_format($sale->initial_payment, 2);
            })

            ->editColumn('balance_finance', function ($sale) {

                return 'S/ ' . number_format($sale->balance_finance, 2);
            })

            ->editColumn('status', function ($sale) {

                $colors = [

                    'activo' => 'success',

                    'cancelado' => 'danger',

                    'rescindido' => 'warning',

                    'finalizado' => 'primary'

                ];

                $color = $colors[$sale->status] ?? 'secondary';

                return '
                    <span class="badge bg-' . $color . ' text-light rounded-pill px-3 py-2 shadow-sm">
                        ' . ucfirst($sale->status) . '
                    </span>
                ';
            })

            ->addColumn('acciones', function ($sale) {

                return view(
                    'admin.sales.partials.acciones',
                    compact('sale')
                )->render();
            })

            ->rawColumns(['status', 'acciones'])

            ->make(true);
    }

    /**
     * GENERAR CÓDIGO
     */
    public function generateCode()
    {
        $lastSale = Sale::orderBy('id', 'desc')->first();

        $nextNumber = 1;

        if ($lastSale) {

            $lastCode = $lastSale->sale_code;

            $number = (int) filter_var(
                $lastCode,
                FILTER_SANITIZE_NUMBER_INT
            );

            $nextNumber = $number + 1;
        }

        $code = 'VTA' . str_pad(
            $nextNumber,
            5,
            '0',
            STR_PAD_LEFT
        );

        return response()->json([
            'code' => $code
        ]);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'sale_code' => [
                'required',
                'string',
                'max:100',
                'unique:sales,sale_code'
            ],

            'customer_id' => [
                'required',
                'exists:customers,id'
            ],

            'lot_id' => [
                'required',
                'exists:lots,id'
            ],

            'sale_type' => [
                'required',
                'in:contado,financiado'
            ],

            'sale_date' => [
                'required',
                'date'
            ],

            'lot_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'initial_payment' => [
                'required',
                'numeric',
                'min:0'
            ],

            'balance_finance' => [
                'required',
                'numeric',
                'min:0'
            ],

            'installments_count' => [
                'required',
                'integer',
                'min:1'
            ],

            'payment_mode' => [
                'required',
                'in:automatico,personalizado'
            ],

            'custom_payment' => [
                'nullable',
                'numeric',
                'min:1'
            ],

            'monthly_payment' => [
                'required',
                'numeric',
                'min:0'
            ],

            'interest_rate' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'first_payment_date' => [
                'nullable',
                'date'
            ],

            'payment_day' => [
                'nullable',
                'integer',
                'min:1',
                'max:31'
            ],

            'late_fee_setting_id' => [
                'nullable',
                'exists:late_fee_settings,id'
            ],

            'status' => [
                'required',
                'in:activo,cancelado,rescindido,finalizado'
            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================================
            // VALIDAR LOTE YA VENDIDO
            // =====================================================

            $exists = Sale::where('lot_id', $data['lot_id'])
                ->where('status', 'activo')
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'lot_id' => [
                            'Este lote ya tiene una venta activa.'
                        ]
                    ]

                ], 422);
            }

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            // =====================================================
            // CREAR VENTA
            // =====================================================
            $data['late_fee_setting_id'] =
                $request->late_fee_setting_id ?: null;
            $sale = Sale::create($data);

            $this->generatePaymentSchedules($sale, $data);

            // =====================================================
            // GENERAR CRONOGRAMA DE PAGOS
            // =====================================================



            // =====================================================
            // ACTUALIZAR ESTADO DEL LOTE
            // =====================================================

            $lot = Lot::find($data['lot_id']);

            if ($lot) {

                // SI EL SALDO ES 0 => VENDIDO
                if ((float) $data['balance_finance'] <= 0) {

                    $lot->status = 'vendido';
                } else {

                    // SI AÚN DEBE => SEPARADO
                    $lot->status = 'separado';
                }

                $lot->save();
            }

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Venta registrada correctamente.',

                'data' => $sale

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error creating sale: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar la venta.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $sale = Sale::find($id);

        if (! $sale) {

            return response()->json([

                'status' => 'error',

                'message' => 'Venta no encontrada.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $sale

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $sale = Sale::find($id);

        if (! $sale) {

            return response()->json([

                'status' => 'error',

                'message' => 'Venta no encontrada.'

            ], 404);
        }

        $data = $request->validate([

            'sale_code' => [
                'required',
                'string',
                'max:100',
                'unique:sales,sale_code,' . $sale->id
            ],

            'customer_id' => [
                'required',
                'exists:customers,id'
            ],

            'lot_id' => [
                'required',
                'exists:lots,id'
            ],

            'sale_date' => [
                'required',
                'date'
            ],

            'lot_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'initial_payment' => [
                'required',
                'numeric',
                'min:0'
            ],

            'balance_finance' => [
                'required',
                'numeric',
                'min:0'
            ],

            'installments_count' => [
                'required',
                'integer',
                'min:1'
            ],

            'payment_mode' => [
                'required',
                'in:automatico,personalizado'
            ],

            'custom_payment' => [
                'nullable',
                'numeric',
                'min:1'
            ],

            'monthly_payment' => [
                'required',
                'numeric',
                'min:0'
            ],

            'interest_rate' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'first_payment_date' => [
                'nullable',
                'date'
            ],

            'payment_day' => [
                'nullable',
                'integer',
                'min:1',
                'max:31'
            ],
            'late_fee_setting_id' => [
                'nullable',
                'exists:late_fee_settings,id'
            ],

            'status' => [
                'required',
                'in:activo,cancelado,rescindido,finalizado'
            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================================
            // VALIDAR LOTE YA VENDIDO
            // =====================================================

            $exists = Sale::where('lot_id', $data['lot_id'])
                ->where('id', '!=', $sale->id)
                ->where('status', 'activo')
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'lot_id' => [
                            'Este lote ya tiene una venta activa.'
                        ]
                    ]

                ], 422);
            }

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            // =====================================================
            // GUARDAR LOTE ANTERIOR
            // =====================================================

            $oldLotId = $sale->lot_id;

            // =====================================================
            // ACTUALIZAR VENTA
            // =====================================================

            $data['late_fee_setting_id'] =
                $request->late_fee_setting_id ?: null;

            $sale->update($data);

            PaymentSchedule::where('sale_id', $sale->id)->delete();


            $this->generatePaymentSchedules($sale, $data);

            // =====================================================
            // ELIMINAR CRONOGRAMA ANTERIOR
            // =====================================================


            // =====================================================
            // GENERAR CRONOGRAMA DE PAGOS
            // =====================================================


            // =====================================================
            // ACTUALIZAR ESTADO NUEVO LOTE
            // =====================================================

            $newLot = Lot::find($data['lot_id']);

            if ($newLot) {

                if ((float) $data['balance_finance'] <= 0) {

                    $newLot->status = 'vendido';
                } else {

                    $newLot->status = 'separado';
                }

                $newLot->save();
            }

            // =====================================================
            // SI CAMBIÓ DE LOTE
            // RESTAURAR EL ANTERIOR
            // =====================================================

            if ($oldLotId != $data['lot_id']) {

                $oldLot = Lot::find($oldLotId);

                if ($oldLot) {

                    $oldLot->status = 'disponible';

                    $oldLot->save();
                }
            }

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Venta actualizada correctamente.',

                'data' => $sale->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error updating sale: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar la venta.',

                'error' => $e->getMessage()

            ], 500);
        }
    }


    /**
     * CRONOGRAMA DE PAGOS
     */
    public function paymentSchedule($id)
    {
        $sale = Sale::with([
            'lateFeeSetting',
            'paymentSchedules' => function ($q) {
                $q->orderBy('installment_number')->orderBy('id');
            }
        ])->find($id);

        if (! $sale) {



            return response()->json([

                'status' => 'error',

                'message' => 'Venta no encontrada.'

            ], 404);
        }

        $totalAmortizado = DB::table('payments')
            ->where('sale_id', $sale->id)
            ->where('payment_type', 'amortizacion')
            ->sum('amount');

        $totalDescuentos = DB::table('amortizations')
            ->where('sale_id', $sale->id)
            ->sum('discount_amount');

        $schedules = $sale->paymentSchedules->map(function ($schedule) use ($sale) {
            $schedule->late_fee = $this->calculateLateFeeForSchedule(
                $schedule,
                $sale->lateFeeSetting
            );

            // =====================================================
            // TOTAL REAL = CUOTA + MORA
            // =====================================================

            $schedule->total_real =
                (float)$schedule->total_amount +
                (float)$schedule->late_fee;

            // =====================================================
            // SI YA ESTÁ PAGADO
            // =====================================================

            if ($schedule->status === 'pagado') {

                $schedule->remaining_balance = 0;
            }

            return $schedule;
        });

        // =====================================================
        // PAGOS NORMALES
        // =====================================================

        $pagos = DB::table('payments')
            ->where('sale_id', $sale->id)
            ->whereIn('payment_type', ['inicial', 'cuota'])
            ->select(
                'payment_date as date',
                'payment_type',
                'amount',
                DB::raw('NULL as recalculation_type'),
                DB::raw('NULL as reduced_installments'),
                DB::raw('NULL as new_installment'),
                DB::raw('0 as discount_amount')
            )
            ->get();

        // =====================================================
        // AMORTIZACIONES
        // =====================================================

        $amortizaciones = DB::table('amortizations')
            ->where('sale_id', $sale->id)
            ->select(
                'date',
                DB::raw('"amortizacion" as payment_type'),
                'amount',
                'recalculation_type',
                'reduced_installments',
                'new_installment',
                'discount_amount'
            )
            ->get();

        // =====================================================
        // UNIR HISTORIAL
        // =====================================================

        $historial = $pagos
            ->concat($amortizaciones)
            ->sortBy('date')
            ->values();

        return response()->json([

            'status' => 'success',

            'sale' => $sale,

            'schedules' => $schedules,

            'total_amortizado' => $totalAmortizado,

            'total_descuentos' => $totalDescuentos,

            'history' => $historial,

        ]);
    }
    /**
     * DELETE
     */


    private function generatePaymentSchedules(Sale $sale, array $data): void
    {
        PaymentSchedule::where('sale_id', $sale->id)->delete();

        $createdBy = Auth::id();
        $saleDate = Carbon::parse($data['sale_date']);

        // =====================================================
        // CONTADO
        // =====================================================
        if (($data['sale_type'] ?? 'financiado') === 'contado') {

            PaymentSchedule::create([
                'sale_id' => $sale->id,
                'schedule_type' => 'contado',
                'installment_number' => 0,
                'due_date' => $saleDate->format('Y-m-d'),
                'installment_amount' => round((float) $data['lot_price'], 2),
                'capital' => round((float) $data['lot_price'], 2),
                'interest' => 0,
                'late_fee' => 0,
                'total_amount' => round((float) $data['lot_price'], 2),
                'remaining_balance' => 0,
                'status' => 'pendiente',
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);

            return;
        }

        // =====================================================
        // FINANCIADO
        // =====================================================

        // Inicial
        PaymentSchedule::create([
            'sale_id' => $sale->id,
            'schedule_type' => 'inicial',
            'installment_number' => 0,
            'due_date' => $saleDate->format('Y-m-d'),
            'installment_amount' => round((float) $data['initial_payment'], 2),
            'capital' => round((float) $data['initial_payment'], 2),
            'interest' => 0,
            'late_fee' => 0,
            'total_amount' => round((float) $data['initial_payment'], 2),
            'remaining_balance' => round((float) $data['balance_finance'], 2),
            'status' => 'pendiente',
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);

        // Cuotas
        $saldo = (float) $data['balance_finance'];
        $cuotas = (int) $data['installments_count'];
        $interes = (float) ($data['interest_rate'] ?? 0);
        $fechaPago = Carbon::parse($data['first_payment_date']);

        $saldoRestante = $saldo;

        $paymentMode = $data['payment_mode'] ?? 'automatico';

        if ($paymentMode === 'personalizado') {

            $cuotaPersonalizada = (float) ($data['custom_payment'] ?? 0);

            if ($cuotaPersonalizada <= 0) {
                throw new \Exception(
                    'Debe ingresar una cuota personalizada válida.'
                );
            }

            for ($i = 1; $i <= $cuotas; $i++) {

                $montoInteres = ($saldoRestante * $interes) / 100;

                if ($i < $cuotas) {

                    $capitalCuota = min(
                        $cuotaPersonalizada,
                        $saldoRestante
                    );
                } else {

                    // última cuota
                    $capitalCuota = $saldoRestante;
                }

                $totalCuota = $capitalCuota + $montoInteres;

                $saldoRestante -= $capitalCuota;

                if ($saldoRestante < 0) {
                    $saldoRestante = 0;
                }

                PaymentSchedule::create([
                    'sale_id' => $sale->id,
                    'schedule_type' => 'cuota',
                    'installment_number' => $i,
                    'due_date' => $fechaPago->copy()
                        ->addMonths($i - 1)
                        ->format('Y-m-d'),
                    'installment_amount' => round($totalCuota, 2),
                    'capital' => round($capitalCuota, 2),
                    'interest' => round($montoInteres, 2),
                    'late_fee' => 0,
                    'total_amount' => round($totalCuota, 2),
                    'remaining_balance' => round($saldoRestante, 2),
                    'status' => 'pendiente',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]);
            }
        } else {

            $capitalCuota = $saldo / $cuotas;

            for ($i = 1; $i <= $cuotas; $i++) {

                $montoInteres = ($saldoRestante * $interes) / 100;

                $totalCuota = $capitalCuota + $montoInteres;

                $saldoRestante -= $capitalCuota;

                if ($saldoRestante < 0) {
                    $saldoRestante = 0;
                }

                PaymentSchedule::create([
                    'sale_id' => $sale->id,
                    'schedule_type' => 'cuota',
                    'installment_number' => $i,
                    'due_date' => $fechaPago->copy()
                        ->addMonths($i - 1)
                        ->format('Y-m-d'),
                    'installment_amount' => round($totalCuota, 2),
                    'capital' => round($capitalCuota, 2),
                    'interest' => round($montoInteres, 2),
                    'late_fee' => 0,
                    'total_amount' => round($totalCuota, 2),
                    'remaining_balance' => round($saldoRestante, 2),
                    'status' => 'pendiente',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]);
            }
        }
    }


    private function calculateLateFeeForSchedule($schedule, $lateFeeSetting): float
    {
        if (!$lateFeeSetting) {
            return 0;
        }

        /*    if ($schedule->status === 'pagado') {
            return 0;
        }
 */
        $today = Carbon::today();
        $dueDate = Carbon::parse($schedule->due_date)->startOfDay();

        if ($today->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        $graceDays = (int) $lateFeeSetting->grace_days;
        $dailyLateFee = (float) $lateFeeSetting->daily_late_fee;
        $maxLateFee = $lateFeeSetting->max_late_fee !== null
            ? (float) $lateFeeSetting->max_late_fee
            : null;

        $holidayDates = collect();

        if ((int) $lateFeeSetting->apply_holidays === 0) {

            $holidayDates = Holiday::where('status', 'activo')
                ->pluck('date')
                ->map(function ($date) {

                    return Carbon::parse($date)
                        ->toDateString();
                })
                ->flip();
        }
        $lateDays = 0;
        $startDate = $dueDate->copy()->addDay();

        for ($date = $startDate; $date->lte($today); $date->addDay()) {

            $isSunday = $date->isSunday();
            $isHoliday = $holidayDates->has($date->toDateString());

            if ((int) $lateFeeSetting->apply_sundays === 0 && $isSunday) {
                continue;
            }

            if ((int) $lateFeeSetting->apply_holidays === 0 && $isHoliday) {
                continue;
            }

            $lateDays++;
        }

        $lateDays = max(0, $lateDays - $graceDays);

        $lateFee = $lateDays * $dailyLateFee;

        if ($maxLateFee !== null && $lateFee > $maxLateFee) {
            $lateFee = $maxLateFee;
        }

        return round($lateFee, 2);
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();

        try {

            // =====================================================
            // RESTAURAR LOTE
            // =====================================================

            $lot = Lot::find($sale->lot_id);

            if ($lot) {

                $lot->status = 'disponible';

                $lot->save();
            }

            // =====================================================
            // ELIMINAR VENTA
            // =====================================================

            $sale->delete();

            DB::commit();

            return response()->json([

                'message' => 'Venta eliminada correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'message' => 'Error al eliminar la venta.',
                'error' => $e->getMessage()

            ], 500);
        }
    }
}
