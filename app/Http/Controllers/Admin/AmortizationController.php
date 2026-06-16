<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Amortization;
use App\Models\Sale;
use App\Models\Payment;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSchedule;
use App\Models\Bank;

use Yajra\DataTables\Facades\DataTables;

class AmortizationController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $sales = Sale::where('status', 'activo')
            ->withCount([
                'paymentSchedules as paid_installments_count' => function ($q) {

                    $q->where('schedule_type', 'cuota')
                        ->where('status', 'pagado');
                }
            ])
            ->orderBy('sale_code')
            ->get();

        // =========================
        // BANCOS ACTIVOS
        // =========================
        $banks = Bank::where('status', 'activo')
            ->orderBy('bank_name')
            ->get();

        return view(
            'admin.amortizations.index',
            compact(
                'sales',
                'banks'
            )
        );
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $amortizations = Amortization::with([
            'sale',
            'payment',
            'creator',
            'updater'
        ])
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($amortizations)

            ->addIndexColumn()

            ->addColumn('sale', function ($item) {

                return $item->sale->sale_code ?? '—';
            })

            ->addColumn('payment', function ($item) {

                return $item->payment
                    ? 'PAGO #' . $item->payment->id
                    : '—';
            })

            ->editColumn('date', function ($item) {

                return $item->date
                    ? date('d/m/Y', strtotime($item->date))
                    : '—';
            })

            ->editColumn('amount', function ($item) {

                return 'S/ ' .
                    number_format($item->amount, 2);
            })

            ->editColumn('recalculation_type', function ($item) {

                $types = [

                    'reducir_cuota' =>
                    'REDUCIR CUOTA',

                    'reducir_tiempo' =>
                    'REDUCIR TIEMPO',

                ];

                return $types[$item->recalculation_type]
                    ?? '—';
            })

            ->editColumn('reduced_installments', function ($item) {

                return $item->reduced_installments
                    ?? '—';
            })

            ->editColumn('new_installment', function ($item) {

                return $item->new_installment
                    ? 'S/ ' .
                    number_format($item->new_installment, 2)
                    : '—';
            })

            ->addColumn('status', function ($item) {

                return '
                    <span class="badge bg-success text-light rounded-pill px-3 py-2 shadow-sm">
                        ACTIVO
                    </span>
                ';
            })

            ->addColumn('acciones', function ($item) {

                return view(
                    'admin.amortizations.partials.acciones',
                    compact('item')
                )->render();
            })

            ->rawColumns([
                'status',
                'acciones'
            ])

            ->make(true);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'sale_id' => [
                'required',
                'exists:sales,id'
            ],



            'date' => [
                'required',
                'date'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'recalculation_type' => [
                'required',
                'in:reducir_cuota,reducir_tiempo,descuento'
            ],

            'reduced_installments' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'new_installment' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'observation' => [
                'nullable',
                'string'
            ],

            'payment_method' => [
                'required',
                'in:efectivo,transferencia,deposito,yape,plin'
            ],

            'bank_id' => [
                'nullable',
                'exists:banks,id'
            ],

            'operation_number' => [
                'nullable',
                'string',
                'max:255'
            ],

        ]);

        DB::beginTransaction();

        try {

            // =====================================================
            // USUARIO
            // =====================================================

            $data['created_by'] = Auth::id();

            $data['updated_by'] = Auth::id();

            // =====================================================
            // CREAR AMORTIZACIÓN
            // =====================================================

            // =====================================================
            // CREAR PAGO AUTOMÁTICO DE AMORTIZACIÓN
            // =====================================================

            $payment = Payment::create([

                'sale_id' => $data['sale_id'],

                'payment_schedule_id' => null,

                'payment_type' => 'amortizacion',

                'payment_date' => $data['date'],

                'amount' => $data['amount'],

                'late_fee_paid' => 0,

                'discount' => 0,

                'observation' =>
                'Pago generado automáticamente por amortización.',

                'payment_method' => $data['payment_method'],

                'bank_id' => in_array(
                    $data['payment_method'],
                    ['transferencia', 'deposito']
                )
                    ? ($data['bank_id'] ?? null)
                    : null,

                'operation_number' => in_array(
                    $data['payment_method'],
                    ['transferencia', 'deposito']
                )
                    ? ($data['operation_number'] ?? null)
                    : null,

                'user_id' => Auth::id(),

                'status' => 'activo',

                'created_by' => Auth::id(),

                'updated_by' => Auth::id(),

            ]);

            // =====================================================
            // RELACIONAR PAYMENT
            // =====================================================
            // =====================================================
            // OBTENER CRONOGRAMA PENDIENTE
            // =====================================================

            $schedules = PaymentSchedule::where('sale_id', $data['sale_id'])
                ->where('schedule_type', 'cuota')
                ->whereIn('status', ['pendiente', 'vencido', 'parcial'])
                ->orderBy('installment_number')
                ->get();
            // =====================================================
            // VALIDAR
            // =====================================================

            if ($schedules->isEmpty()) {

                throw new \Exception(
                    'No existen cuotas pendientes para recalcular.'
                );
            }

            // =====================================================
            // DATOS BASE
            // =====================================================

            // cuota mensual actual
            $cuotaActual = (float) $schedules->first()->installment_amount;

            // saldo REAL pendiente
            // =====================================================
            // SALDO REAL PENDIENTE
            // =====================================================

            // =====================================================
            // SALDO REAL PENDIENTE
            // =====================================================

            // total financiado
            $totalFinanciado = Sale::find($data['sale_id'])->balance_finance;

            // total pagado SOLO cuotas normales
            $totalPagadoCuotas = Payment::where('sale_id', $data['sale_id'])
                ->whereIn('payment_type', [
                    'cuota',
                    'amortizacion'
                ])
                ->where('status', 'activo')
                ->sum('amount');

            // saldo pendiente real
            $totalPendiente = $totalFinanciado - $totalPagadoCuotas;

            // monto amortizado
            $montoAmortizado = (float) $data['amount'];

            $descuento =
                (float) ($data['discount_amount'] ?? 0);

            // =====================================================
            // NUEVO SALDO
            // =====================================================
            $nuevoSaldo =
                $totalPendiente
                - $descuento;

            if ($nuevoSaldo < 0) {

                $nuevoSaldo = 0;
            }

            // =====================================================
            // REDUCIR TIEMPO
            // =====================================================
            if ($data['recalculation_type'] == 'reducir_tiempo') {

                // =================================================
                // CUOTA ACTUAL
                // =================================================

                $cuotaActual = (float) $schedules->first()->installment_amount;

                // =================================================
                // NUEVAS CUOTAS
                // =================================================

                $nuevasCuotas = (int) round($nuevoSaldo / $cuotaActual);

                if ($nuevoSaldo <= 0) {
                    $nuevasCuotas = 0;
                }

                // =================================================
                // CUOTAS REDUCIDAS
                // =================================================

                $totalOriginalCuotas = Sale::find($data['sale_id'])
                    ->installments_count;


                $cuotasPagadas = PaymentSchedule::where('sale_id', $data['sale_id'])
                    ->where('schedule_type', 'cuota')
                    ->where('status', 'pagado')
                    ->count();

                $totalFinalCuotas =
                    $cuotasPagadas + $nuevasCuotas;

                $data['reduced_installments'] =
                    $totalOriginalCuotas - $totalFinalCuotas;
                $data['new_installment'] = null;

                // =================================================
                // OBTENER ÚLTIMA CUOTA PAGADA
                // =================================================

                $ultimaPagada = PaymentSchedule::where('sale_id', $data['sale_id'])
                    ->where('schedule_type', 'cuota')
                    ->where('status', 'pagado')
                    ->orderByDesc('installment_number')
                    ->first();

                // =================================================
                // NÚMERO INICIAL
                // =================================================

                $numeroInicial = $ultimaPagada
                    ? $ultimaPagada->installment_number + 1
                    : 1;

                // =================================================
                // FECHA INICIAL
                // =================================================

                $fechaInicial = $ultimaPagada
                    ? \Carbon\Carbon::parse($ultimaPagada->due_date)->addMonth()
                    : now();

                // =================================================
                // ELIMINAR TODAS LAS CUOTAS PENDIENTES
                // =================================================

                PaymentSchedule::where('sale_id', $data['sale_id'])
                    ->where('schedule_type', 'cuota')
                    ->whereIn('status', ['pendiente', 'vencido', 'parcial'])
                    ->delete();

                // =================================================
                // CREAR NUEVO CRONOGRAMA
                // =================================================

                $saldoRestante = $nuevoSaldo;

                for ($i = 1; $i <= $nuevasCuotas; $i++) {

                    // última cuota ajustada
                    if ($i == $nuevasCuotas) {

                        $montoCuota = $saldoRestante;
                    } else {

                        $montoCuota = $cuotaActual;
                    }

                    $saldoRestante -= $montoCuota;

                    if ($saldoRestante < 0) {
                        $saldoRestante = 0;
                    }

                    PaymentSchedule::create([

                        'sale_id' => $data['sale_id'],

                        'installment_number' =>
                        $numeroInicial + ($i - 1),

                        'due_date' =>
                        $fechaInicial->copy()->addMonths($i - 1),

                        'installment_amount' =>
                        $montoCuota,

                        'capital' =>
                        $montoCuota,

                        'interest' => 0,

                        'late_fee' => 0,

                        'total_amount' =>
                        $montoCuota,

                        'remaining_balance' =>
                        $saldoRestante,

                        'status' => 'pendiente',

                        'created_by' => Auth::id(),

                        'updated_by' => Auth::id(),

                    ]);
                }


                // actualizar nueva cantidad total
                $sale = Sale::find($data['sale_id']);

                $sale->installments_count = $totalFinalCuotas;

                $sale->updated_by = Auth::id();

                $sale->save();
            }

            // =====================================================
            // REDUCIR CUOTA
            // =====================================================
            if ($data['recalculation_type'] == 'reducir_cuota') {

                // ================================================
                // CUOTAS PENDIENTES
                // ================================================

                $cuotasPendientes = $schedules->count();

                if ($cuotasPendientes <= 0) {

                    throw new \Exception(
                        'No existen cuotas pendientes.'
                    );
                }

                // ================================================
                // NUEVA CUOTA
                // ================================================

                $nuevaCuota =
                    round($nuevoSaldo / $cuotasPendientes, 2);

                $data['new_installment'] = $nuevaCuota;

                $data['reduced_installments'] = 0;

                // ================================================
                // ÚLTIMA PAGADA
                // ================================================

                $ultimaPagada = PaymentSchedule::where(
                    'sale_id',
                    $data['sale_id']
                )
                    ->where('status', 'pagado')
                    ->orderByDesc('installment_number')
                    ->first();

                // ================================================
                // NUMERACIÓN
                // ================================================

                $numeroInicial = $ultimaPagada
                    ? $ultimaPagada->installment_number + 1
                    : 1;

                // ================================================
                // FECHA INICIAL
                // ================================================

                $fechaInicial = $ultimaPagada
                    ? \Carbon\Carbon::parse(
                        $ultimaPagada->due_date
                    )->addMonth()
                    : now();

                // ================================================
                // ELIMINAR PENDIENTES
                // ================================================

                PaymentSchedule::where(
                    'sale_id',
                    $data['sale_id']
                )
                    ->whereIn('status', [
                        'pendiente',
                        'vencido',
                        'parcial'
                    ])
                    ->delete();

                // ================================================
                // CREAR NUEVO CRONOGRAMA
                // ================================================

                $saldoRestante = $nuevoSaldo;

                for ($i = 1; $i <= $cuotasPendientes; $i++) {

                    // última cuota ajustada
                    if ($i == $cuotasPendientes) {

                        $montoCuota = $saldoRestante;
                    } else {

                        $montoCuota = $nuevaCuota;
                    }

                    $saldoRestante -= $montoCuota;

                    if ($saldoRestante < 0) {

                        $saldoRestante = 0;
                    }

                    PaymentSchedule::create([

                        'sale_id' => $data['sale_id'],

                        'installment_number' =>
                        $numeroInicial + ($i - 1),

                        'due_date' =>
                        $fechaInicial->copy()->addMonths($i - 1),

                        'installment_amount' =>
                        $montoCuota,

                        'capital' =>
                        $montoCuota,

                        'interest' => 0,

                        'late_fee' => 0,

                        'total_amount' =>
                        $montoCuota,

                        'remaining_balance' =>
                        $saldoRestante,

                        'status' => 'pendiente',

                        'created_by' => Auth::id(),

                        'updated_by' => Auth::id(),

                    ]);
                }
            }


            // =====================================================
            // DESCUENTO
            // =====================================================
            if ($data['recalculation_type'] == 'descuento') {

                // =================================================
                // CUOTA ACTUAL
                // =================================================

                $cuotaActual = (float) $schedules->first()->installment_amount;

                // =================================================
                // NUEVAS CUOTAS
                // =================================================

                $nuevasCuotas = (int) ceil($nuevoSaldo / $cuotaActual);

                if ($nuevoSaldo <= 0) {

                    $nuevasCuotas = 0;
                }

                // =================================================
                // CUOTAS REDUCIDAS
                // =================================================

                $totalOriginalCuotas = Sale::find($data['sale_id'])
                    ->installments_count;

                $cuotasPagadas = PaymentSchedule::where(
                    'sale_id',
                    $data['sale_id']
                )
                    ->where('status', 'pagado')
                    ->count();

                $totalFinalCuotas =
                    $cuotasPagadas + $nuevasCuotas;

                $data['reduced_installments'] =
                    $totalOriginalCuotas - $totalFinalCuotas;

                $data['new_installment'] = null;

                // =================================================
                // ÚLTIMA PAGADA
                // =================================================

                $ultimaPagada = PaymentSchedule::where(
                    'sale_id',
                    $data['sale_id']
                )
                    ->where('status', 'pagado')
                    ->orderByDesc('installment_number')
                    ->first();

                // =================================================
                // NUMERACIÓN
                // =================================================

                $numeroInicial = $ultimaPagada
                    ? $ultimaPagada->installment_number + 1
                    : 1;

                // =================================================
                // FECHA INICIAL
                // =================================================

                $fechaInicial = $ultimaPagada
                    ? \Carbon\Carbon::parse(
                        $ultimaPagada->due_date
                    )->addMonth()
                    : now();

                // =================================================
                // ELIMINAR PENDIENTES
                // =================================================

                PaymentSchedule::where(
                    'sale_id',
                    $data['sale_id']
                )
                    ->whereIn('status', [
                        'pendiente',
                        'vencido',
                        'parcial'
                    ])
                    ->delete();

                // =================================================
                // CREAR NUEVO CRONOGRAMA
                // =================================================

                $saldoRestante = $nuevoSaldo;

                for ($i = 1; $i <= $nuevasCuotas; $i++) {

                    if ($i == $nuevasCuotas) {

                        $montoCuota = $saldoRestante;
                    } else {

                        $montoCuota = $cuotaActual;
                    }

                    $saldoRestante -= $montoCuota;

                    if ($saldoRestante < 0) {

                        $saldoRestante = 0;
                    }

                    PaymentSchedule::create([

                        'sale_id' => $data['sale_id'],

                        'installment_number' =>
                        $numeroInicial + ($i - 1),

                        'due_date' =>
                        $fechaInicial->copy()->addMonths($i - 1),

                        'installment_amount' =>
                        $montoCuota,

                        'capital' =>
                        $montoCuota,

                        'interest' => 0,

                        'late_fee' => 0,

                        'total_amount' =>
                        $montoCuota,

                        'remaining_balance' =>
                        $saldoRestante,

                        'status' => 'pendiente',

                        'created_by' => Auth::id(),

                        'updated_by' => Auth::id(),

                    ]);
                }

                // =================================================
                // ACTUALIZAR TOTAL CUOTAS
                // =================================================

                $sale = Sale::find($data['sale_id']);

                $sale->installments_count = $totalFinalCuotas;

                $sale->updated_by = Auth::id();

                $sale->save();
            }

            // =====================================================
            // RELACIONAR PAYMENT
            // =====================================================

            $data['payment_id'] = $payment->id;

            // =====================================================
            // CREAR AMORTIZACIÓN
            // =====================================================

            $data['discount_amount'] =
                $data['discount_amount'] ?? 0;

            $amortization = Amortization::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' =>
                'Amortización registrada correctamente.',

                'data' => $amortization

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error amortization: ' .
                    $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' =>
                'Error al registrar amortización.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * SHOW
     */
    public function show($id)
    {
        $amortization = Amortization::with([
            'sale',
            'payment',
            'creator',
            'updater'
        ])->find($id);

        if (!$amortization) {

            return response()->json([

                'status' => 'error',

                'message' =>
                'Amortización no encontrada.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $amortization

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        return response()->json([

            'status' => 'warning',

            'message' =>
            'Las amortizaciones no pueden editarse.'

        ], 403);
    }

    /**
     * DESTROY
     */
    public function destroy($id)
    {
        return response()->json([

            'status' => 'warning',

            'message' =>
            'Las amortizaciones no pueden eliminarse.'

        ], 403);
    }
}
