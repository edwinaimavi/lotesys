<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\PaymentDetail;

use App\Models\Sale;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Bank;
use App\Models\Lot;
use Carbon\Carbon;
use App\Models\Holiday;

use App\Services\ApisPeruService;

class PaymentController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $sales = Sale::whereHas('paymentSchedules', function ($q) {

            $q->whereIn('status', [
                'pendiente',
                'parcial',
                'vencido'
            ]);
        })
            ->orderBy('sale_code')
            ->get();

        $paymentSchedules = PaymentSchedule::with('sale')
            ->orderBy('id', 'desc')
            ->get();

        // ============================================
        // BANCOS ACTIVOS
        // ============================================

        $banks = Bank::where('status', 'activo')
            ->orderBy('bank_name')
            ->get();

        return view('admin.payments.index', compact(
            'sales',
            'paymentSchedules',
            'banks'
        ));
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $payments = Payment::with([
            'sale',
            'details.paymentSchedule',
            'creator',
            'updater',
            'invoice'
        ])
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($payments)

            ->addIndexColumn()

            ->addColumn('sale', function ($payment) {

                return $payment->sale->sale_code ?? '—';
            })

            ->addColumn('installment', function ($payment) {

                if ($payment->details->isEmpty()) {
                    return '—';
                }

                $installments = $payment->details
                    ->map(function ($detail) {

                        return 'CUOTA #' .
                            $detail->paymentSchedule?->installment_number;
                    })
                    ->unique()
                    ->implode(', ');

                return $installments ?: '—';
            })

            ->editColumn('payment_date', function ($payment) {

                return $payment->payment_date
                    ? date('d/m/Y', strtotime($payment->payment_date))
                    : '—';
            })

            ->editColumn('amount', function ($payment) {

                return 'S/ ' . number_format($payment->amount, 2);
            })

            ->editColumn('late_fee_paid', function ($payment) {

                return 'S/ ' . number_format($payment->late_fee_paid, 2);
            })

            ->editColumn('payment_type', function ($payment) {

                return strtoupper($payment->payment_type);
            })

            ->editColumn('payment_method', function ($payment) {

                return strtoupper($payment->payment_method);
            })

            ->editColumn('status', function ($payment) {

                $colors = [

                    'activo' => 'success',

                    'anulado' => 'danger'

                ];

                $color = $colors[$payment->status] ?? 'secondary';

                return '
                <span class="badge bg-' . $color . ' text-light rounded-pill px-3 py-2 shadow-sm">
                    ' . ucfirst($payment->status) . '
                </span>
            ';
            })

            ->addColumn('acciones', function ($payment) {

                return view(
                    'admin.payments.partials.acciones',
                    compact('payment')
                )->render();
            })

            ->rawColumns(['status', 'acciones'])

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

            'payment_type' => [
                'required',
                'in:inicial,cuota,amortizacion,cancelacion_total,mora'
            ],

            'payment_date' => [
                'required',
                'date'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0'
            ],

            'late_fee_paid' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'discount' => [
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
                'in:efectivo,transferencia,yape,plin,deposito'
            ],

            'bank_id' => [
                'nullable',
                'exists:banks,id'
            ],

            'operation_number' => [
                'nullable',
                'string',
                'max:100'
            ],

            'status' => [
                'required',
                'in:activo,anulado'
            ],

            'payment_details' => [
                'required',
                'array',
                'min:1'
            ],

        ]);

        try {

            DB::beginTransaction();

            // =====================================================
            // USUARIO
            // =====================================================

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();

                $data['user_id'] = Auth::id();
            }

            // =====================================================
            // CREAR PAGO
            // =====================================================
            $payment = Payment::create([

                'sale_id'           => $data['sale_id'],

                // cuota principal referencial
                'payment_schedule_id' =>
                $request->payment_details[0]['payment_schedule_id']
                    ?? null,

                'payment_type'      => $data['payment_type'],
                'payment_date'      => $data['payment_date'],
                'amount'            => $data['amount'],
                'late_fee_paid'     => $data['late_fee_paid'] ?? 0,
                'discount'          => $data['discount'] ?? 0,
                'observation'       => $data['observation'] ?? null,
                'payment_method'    => $data['payment_method'],
                'bank_id'           => $data['bank_id'] ?? null,

                'operation_number'  => $data['operation_number'] ?? null,
                'status'            => $data['status'],
                'created_by'        => $data['created_by'],
                'updated_by'        => $data['updated_by'],
                'user_id'           => $data['user_id']

            ]);

            // =====================================================
            // GUARDAR DETALLES
            // =====================================================

            foreach ($request->payment_details as $detail) {

                $scheduleId = $detail['payment_schedule_id'];

                $appliedAmount = $detail['applied_amount'];

                // =================================================
                // CREAR DETALLE
                // =================================================

                PaymentDetail::create([

                    'payment_id'           => $payment->id,

                    'payment_schedule_id'  => $scheduleId,

                    'applied_amount'       => $appliedAmount,

                    'created_by'           => Auth::id(),

                    'updated_by'           => Auth::id(),

                ]);

                // =================================================
                // ACTUALIZAR CRONOGRAMA
                // =================================================

                $schedule = PaymentSchedule::with(
                    'sale.lateFeeSetting'
                )->find($scheduleId);

                if ($schedule) {

                    // TOTAL PAGADO DE ESA CUOTA
                    $totalCapitalPaid = PaymentDetail::where(
                        'payment_schedule_id',
                        $schedule->id
                    )->sum('applied_amount');

                    $totalLateFeePaid = PaymentDetail::where(
                        'payment_details.payment_schedule_id',
                        $schedule->id
                    )
                        ->whereHas('payment', function ($q) {

                            $q->where('status', 'activo');
                        })
                        ->join('payments', 'payments.id', '=', 'payment_details.payment_id')
                        ->sum('payments.late_fee_paid');

                    $totalPaid = $totalCapitalPaid + $totalLateFeePaid;

                    // NUEVO SALDO
                    // =============================================
                    // CALCULAR MORA ACTUAL
                    // =============================================

                    $lateFee = $this->calculateLateFeeForSchedule(
                        $schedule
                    );

                    // =============================================
                    // TOTAL REAL DE LA CUOTA
                    // =============================================

                    // =============================================
                    // GUARDAR MORA EN LA CUOTA
                    // =============================================

                    $schedule->late_fee = round($lateFee, 2);

                    // =============================================
                    // TOTAL REAL DE LA CUOTA
                    // =============================================

                    $realTotal =
                        $schedule->total_amount +
                        $schedule->late_fee;

                    // =============================================
                    // NUEVO SALDO REAL
                    // =============================================

                    $remaining = $realTotal - $totalPaid;

                    if ($remaining < 0) {

                        $remaining = 0;
                    }
                    $schedule->remaining_balance = $remaining;

                    // =============================================
                    // ESTADO
                    // =============================================

                    if ($remaining <= 0) {

                        $schedule->status = 'pagado';
                    } elseif ($totalPaid > 0) {

                        $schedule->status = 'parcial';
                    } else {

                        $schedule->status = 'pendiente';
                    }

                    $schedule->save();
                }
            }

            // =====================================================
            // ACTUALIZAR ESTADO VENTA / LOTE
            // =====================================================

            $this->updateSaleAndLotStatus(
                $data['sale_id']
            );

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Pago registrado correctamente.',

                'data' => $payment

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error creating payment: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar el pago.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $payment = Payment::find($id);

        if (! $payment) {

            return response()->json([

                'status' => 'error',

                'message' => 'Pago no encontrado.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $payment

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (! $payment) {

            return response()->json([

                'status' => 'error',

                'message' => 'Pago no encontrado.'

            ], 404);
        }

        $data = $request->validate([

            'sale_id' => [
                'required',
                'exists:sales,id'
            ],

            'payment_schedule_id' => [
                'nullable',
                'exists:payment_schedules,id'
            ],

            'payment_type' => [
                'required',
                'in:inicial,cuota,amortizacion,cancelacion_total,mora'
            ],

            'payment_date' => [
                'required',
                'date'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0'
            ],

            'late_fee_paid' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'discount' => [
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
                'in:efectivo,transferencia,yape,plin,deposito'
            ],

            'bank_id' => [
                'nullable',
                'exists:banks,id'
            ],

            'operation_number' => [
                'nullable',
                'string',
                'max:100'
            ],

            'status' => [
                'required',
                'in:activo,anulado'
            ]

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            $payment->update($data);

            // =====================================================
            // ACTUALIZAR CRONOGRAMA
            // =====================================================

            if ($payment->payment_schedule_id) {

                $schedule = PaymentSchedule::with(
                    'sale.lateFeeSetting'
                )->find(
                    $payment->payment_schedule_id
                );

                if ($schedule) {

                    $totalPagado = Payment::where(
                        'payment_schedule_id',
                        $schedule->id
                    )
                        ->where('status', 'activo')
                        ->sum('amount');

                    if ($totalPagado >= $schedule->total_amount) {

                        $schedule->status = 'pagado';
                    } elseif ($totalPagado > 0) {

                        $schedule->status = 'parcial';
                    } else {

                        $schedule->status = 'pendiente';
                    }

                    $schedule->save();
                }
            }

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Pago actualizado correctamente.',

                'data' => $payment->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error updating payment: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar el pago.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    /* public function destroy(Payment $payment)
    {
        DB::beginTransaction();

        try {

            $scheduleId = $payment->payment_schedule_id;

            $payment->delete();

            // =====================================================
            // RECALCULAR ESTADO CRONOGRAMA
            // =====================================================

            if ($scheduleId) {

                $schedule = PaymentSchedule::find($scheduleId);

                if ($schedule) {

                    $totalPagado = Payment::where(
                        'payment_schedule_id',
                        $schedule->id
                    )
                        ->where('status', 'activo')
                        ->sum('amount');

                    if ($totalPagado >= $schedule->total_amount) {

                        $schedule->status = 'pagado';
                    } elseif ($totalPagado > 0) {

                        $schedule->status = 'parcial';
                    } else {

                        $schedule->status = 'pendiente';
                    }

                    $schedule->save();
                }
            }

            DB::commit();

            return response()->json([

                'message' => 'Pago eliminado correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'message' => 'Error al eliminar el pago.',

                'error' => $e->getMessage()

            ], 500);
        }
    } */

    /**
     * OBTENER CUOTAS SEGÚN LA VENTA
     */
    public function getSchedules($saleId)
    {
        $schedules = PaymentSchedule::with('sale.lateFeeSetting')
            ->where('sale_id', $saleId)
            ->whereIn('status', [
                'pendiente',
                'parcial',
                'vencido'
            ])
            ->where('remaining_balance', '>', 0)
            ->orderBy('installment_number')
            ->get();

        foreach ($schedules as $schedule) {

            $lateFee = $this->calculateLateFeeForSchedule(
                $schedule
            );

            $schedule->late_fee = round($lateFee, 2);
            $schedule->total_real = round(
                $schedule->total_amount + $schedule->late_fee,
                2
            );
        }

        return response()->json($schedules);
    }

    private function calculateLateFeeForSchedule(PaymentSchedule $schedule): float
    {
        $sale = $schedule->sale;

        if (!$sale || !$sale->lateFeeSetting) {
            return 0;
        }

        $setting = $sale->lateFeeSetting;
        $today = Carbon::today();
        $dueDate = $schedule->getEffectiveDueDate();

        if ($today->lte($dueDate)) {
            return 0;
        }

        $daysLate = 0;
        $current = $dueDate->copy();

        while ($current->lt($today)) {

            $current->addDay();

            $isSunday = $current->dayOfWeek === Carbon::SUNDAY;

            $isHoliday = Holiday::where(
                'date',
                $current->format('Y-m-d')
            )
                ->where('status', 'activo')
                ->exists();

            if (!$setting->apply_sundays && $isSunday) {
                continue;
            }

            if (!$setting->apply_holidays && $isHoliday) {
                continue;
            }

            $daysLate++;
        }

        $daysLate -= (int) $setting->grace_days;

        if ($daysLate < 0) {
            $daysLate = 0;
        }

        $lateFee = $daysLate * (float) $setting->daily_late_fee;

        if (
            $setting->max_late_fee &&
            $lateFee > $setting->max_late_fee
        ) {
            $lateFee = $setting->max_late_fee;
        }

        return round($lateFee, 2);
    }

    private function updateSaleAndLotStatus($saleId): void
    {
        $sale = Sale::with([
            'paymentSchedules',
            'lot'
        ])->find($saleId);

        if (!$sale) {
            return;
        }

        // =====================================================
        // VERIFICAR SI TODAS LAS CUOTAS ESTÁN PAGADAS
        // =====================================================

        $pendingSchedules = $sale->paymentSchedules()
            ->whereIn('status', [
                'pendiente',
                'parcial',
                'vencido'
            ])
            ->count();

        // =====================================================
        // TODO PAGADO
        // =====================================================

        if ($pendingSchedules <= 0) {

            // VENTA
            $sale->status = 'finalizado';
            $sale->save();

            // LOTE
            if ($sale->lot) {

                $sale->lot->status = 'vendido';
                $sale->lot->save();
            }
        }
        // =====================================================
        // AÚN DEBE
        // =====================================================

        else {

            $sale->status = 'activo';
            $sale->save();

            if ($sale->lot) {

                $sale->lot->status = 'separado';
                $sale->lot->save();
            }
        }
    }
    /**
     * ANULAR PAGO
     */
    public function cancel(Payment $payment)
    {
        DB::beginTransaction();

        try {

            // =====================================================
            // VALIDAR
            // =====================================================

            if ($payment->status === 'anulado') {

                return response()->json([

                    'status' => 'warning',

                    'message' => 'El pago ya está anulado.'

                ], 400);
            }

            // =====================================================
            // ANULAR PAGO
            // =====================================================

            $payment->status = 'anulado';

            $payment->updated_by = Auth::id();

            $payment->save();

            // =====================================================
            // RECALCULAR TODAS LAS CUOTAS DEL PAGO
            // =====================================================

            foreach ($payment->details as $detail) {

                $schedule = PaymentSchedule::with('sale.lateFeeSetting')
                    ->find($detail->payment_schedule_id);

                if (!$schedule) {
                    continue;
                }

                // =============================================
                // TOTAL PAGADO SOLO PAGOS ACTIVOS
                // =============================================

                $totalCapitalPaid = PaymentDetail::where(
                    'payment_schedule_id',
                    $schedule->id
                )
                    ->whereHas('payment', function ($q) {

                        $q->where('status', 'activo');
                    })
                    ->sum('applied_amount');

                $totalLateFeePaid = PaymentDetail::where(
                    'payment_details.payment_schedule_id',
                    $schedule->id
                )
                    ->whereHas('payment', function ($q) {

                        $q->where('status', 'activo');
                    })
                    ->join('payments', 'payments.id', '=', 'payment_details.payment_id')
                    ->sum('payments.late_fee_paid');

                $totalPaid = $totalCapitalPaid + $totalLateFeePaid;

                // =============================================
                // NUEVO SALDO
                // =============================================

                // =============================================
                // RECALCULAR MORA ACTUAL
                // =============================================

                $lateFee = $this->calculateLateFeeForSchedule(
                    $schedule
                );

                // =============================================
                // TOTAL REAL
                // =============================================

                // =============================================
                // GUARDAR MORA EN LA CUOTA
                // =============================================

                $schedule->late_fee = round($lateFee, 2);

                // =============================================
                // TOTAL REAL
                // =============================================

                $realTotal =
                    $schedule->total_amount +
                    $schedule->late_fee;

                // =============================================
                // NUEVO SALDO
                // =============================================

                $remaining = $realTotal - $totalPaid;

                if ($remaining < 0) {
                    $remaining = 0;
                }
                $schedule->remaining_balance = $remaining;

                // =============================================
                // ESTADO
                // =============================================

                if ($remaining <= 0) {

                    $schedule->status = 'pagado';
                } elseif ($totalPaid > 0) {

                    $schedule->status = 'parcial';
                } else {

                    $schedule->status = 'pendiente';
                }

                $schedule->save();
            }

            $this->updateSaleAndLotStatus(
                $payment->sale_id
            );

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Pago anulado correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'status' => 'error',

                'message' => 'Error al anular pago.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /*  public function getApisPeruCompanies(
        ApisPeruService $apisPeru
    ) {
        try {

            $login = $apisPeru->login(
                '33alcipi',
                'victoria@1192'
            );

            $companies = $apisPeru->getCompanies(
                $login['token']
            );

            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    } */
}
