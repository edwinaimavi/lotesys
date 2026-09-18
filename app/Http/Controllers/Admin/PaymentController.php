<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\PaymentDetail;
use App\Models\Invoice;

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
use App\Services\CreditNoteService;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $sales = Sale::with([
            'customer',
            'lot.project.company',
            'lot.block',
        ])
            ->whereHas('paymentSchedules', function ($q) {

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
            'sale.customer',
            'sale.lot.project.company',
            'sale.lot.block',
            'details.paymentSchedule',
            'creator',
            'updater',
            'invoice',
            'invoices'
        ])
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($payments)

            ->addIndexColumn()

            ->addColumn('sale', function ($payment) {

                $sale = $payment->sale;
                $customer = $sale?->customer;

                $customerName = $customer?->person_type === 'juridica'
                    ? ($customer->business_name ?: '—')
                    : trim(($customer?->first_name ?? '') . ' ' . ($customer?->last_name ?? ''));

                $customerName = $customerName !== '' ? $customerName : '—';

                return '<div class="text-left payment-sale-reference">'
                    . '<div class="font-weight-bold text-primary">' . e($sale?->sale_code ?? '—') . '</div>'
                    . '<small class="text-muted d-block text-truncate" title="' . e($customerName) . '">' . e($customerName) . '</small>'
                    . '</div>';
            })

            ->addColumn('company', function ($payment) {

                return $payment->sale?->lot?->project?->company?->business_name ?? '—';
            })

            ->addColumn('property', function ($payment) {

                $lot = $payment->sale?->lot;
                $project = $lot?->project?->name ?? '—';
                $block = $lot?->block?->name ?? '—';
                $number = $lot?->number ?? '—';
                $code = $lot?->code ?? '—';

                return '<div class="text-left payment-property-reference">'
                    . '<div class="font-weight-bold text-dark">' . e($project) . '</div>'
                    . '<small class="text-muted d-block">' . e($block) . ' · Lote ' . e($number) . '</small>'
                    . '<small class="text-primary d-block">' . e($code) . '</small>'
                    . '</div>';
            })

            ->addColumn('installment', function ($payment) {
                return $this->formatPaymentInstallmentVisualLabels($payment);
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

                $installmentLabel = $this->formatPaymentInstallmentVisualLabels($payment);

                return view(
                    'admin.payments.partials.acciones',
                    compact('payment', 'installmentLabel')
                )->render();
            })

            ->rawColumns(['sale', 'property', 'status', 'acciones'])

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
                'gt:0'
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

            'payment_details.*.payment_schedule_id' => [
                'required',
                'integer',
                'distinct',
                'exists:payment_schedules,id'
            ],

            'payment_details.*.applied_amount' => [
                'required',
                'numeric',
                'gt:0'
            ],

        ]);

        $paymentDetails = array_values($data['payment_details']);
        $lateFeePaid = round((float) ($data['late_fee_paid'] ?? 0), 2);
        $appliedTotal = round(collect($paymentDetails)
            ->sum(fn($detail) => (float) $detail['applied_amount']), 2);
        $expectedAmount = round($appliedTotal + $lateFeePaid, 2);

        if (abs((float) $data['amount'] - $expectedAmount) > 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'El monto total debe coincidir con la suma de los montos aplicados y la mora.'
            ]);
        }

        $data['amount'] = $expectedAmount;

        try {

            DB::beginTransaction();

            $scheduleIds = collect($paymentDetails)
                ->pluck('payment_schedule_id');

            $schedules = PaymentSchedule::with('sale.lateFeeSetting')
                ->whereIn('id', $scheduleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $activeAppliedAmounts = PaymentDetail::whereIn(
                'payment_schedule_id',
                $scheduleIds
            )
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'activo');
                })
                ->selectRaw('payment_schedule_id, SUM(applied_amount) as total_applied')
                ->groupBy('payment_schedule_id')
                ->pluck('total_applied', 'payment_schedule_id');

            foreach ($paymentDetails as $detail) {
                $schedule = $schedules->get($detail['payment_schedule_id']);

                if (!$schedule || (int) $schedule->sale_id !== (int) $data['sale_id']) {
                    throw ValidationException::withMessages([
                        'payment_details' => 'Todas las cuotas deben pertenecer a la venta seleccionada.'
                    ]);
                }

                $alreadyApplied = (float) (
                    $activeAppliedAmounts[$schedule->id] ?? 0
                );
                $pendingAmount = max(
                    round((float) $schedule->total_amount - $alreadyApplied, 2),
                    0
                );
                $appliedAmount = round((float) $detail['applied_amount'], 2);

                if ($schedule->status === 'pagado' || $pendingAmount <= 0) {
                    throw ValidationException::withMessages([
                        'payment_details' => $this->formatInstallmentVisualLabel(
                            $schedule->installment_number
                        ) . ' ya se encuentra pagada.'
                    ]);
                }

                if ($appliedAmount - $pendingAmount > 0.01) {
                    throw ValidationException::withMessages([
                        'payment_details' => 'El monto aplicado a ' .
                            $this->formatInstallmentVisualLabel(
                                $schedule->installment_number
                            ) . ' no puede superar su saldo pendiente de S/ ' .
                            number_format($pendingAmount, 2, '.', ',') . '.'
                    ]);
                }
            }

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
                $paymentDetails[0]['payment_schedule_id'],

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

            foreach ($paymentDetails as $detail) {

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

                $schedule = $schedules->get($scheduleId);

                if ($schedule) {

                    // TOTAL PAGADO DE ESA CUOTA
                    $totalCapitalPaid = PaymentDetail::where(
                        'payment_schedule_id',
                        $schedule->id
                    )
                        ->whereHas('payment', function ($query) {
                            $query->where('status', 'activo');
                        })
                        ->sum('applied_amount');

                    $totalLateFeePaid = Payment::where(
                        'payment_schedule_id',
                        $schedule->id
                    )
                        ->where('status', 'activo')
                        ->sum('late_fee_paid');

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
        } catch (ValidationException $e) {

            DB::rollBack();

            throw $e;
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
            ->orderBy('installment_number')
            ->get();

        $activeAppliedAmounts = PaymentDetail::whereIn(
            'payment_schedule_id',
            $schedules->pluck('id')
        )
            ->whereHas('payment', function ($query) {
                $query->where('status', 'activo');
            })
            ->selectRaw('payment_schedule_id, SUM(applied_amount) as total_applied')
            ->groupBy('payment_schedule_id')
            ->pluck('total_applied', 'payment_schedule_id');

        foreach ($schedules as $schedule) {

            $lateFee = $this->calculateLateFeeForSchedule(
                $schedule
            );

            $schedule->late_fee = round($lateFee, 2);
            $schedule->pending_amount = max(
                round(
                    (float) $schedule->total_amount -
                        (float) ($activeAppliedAmounts[$schedule->id] ?? 0),
                    2
                ),
                0
            );
            $schedule->total_real = round(
                $schedule->pending_amount + $schedule->late_fee,
                2
            );
            $schedule->installment_label = $this->formatInstallmentVisualLabel(
                $schedule->installment_number
            );
        }

        return response()->json(
            $schedules
                ->filter(fn($schedule) => $schedule->pending_amount > 0)
                ->values()
        );
    }

    private function formatPaymentInstallmentVisualLabels(Payment $payment): string
    {
        $installments = $payment->details
            ->map(function ($detail) {
                $installmentNumber = $detail->paymentSchedule?->installment_number;

                return $installmentNumber === null
                    ? null
                    : $this->formatInstallmentVisualLabel($installmentNumber);
            })
            ->filter()
            ->unique()
            ->implode(', ');

        return $installments ?: '—';
    }

    private function formatInstallmentVisualLabel($installmentNumber): string
    {
        $visualNumber = ((int) $installmentNumber) + 1;

        return (int) $installmentNumber === 0
            ? 'Cuota ' . $visualNumber . ' - Inicial'
            : 'Cuota ' . $visualNumber;
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
     *
     * Si existe una boleta/factura SUNAT aceptada, primero emite una Nota de
     * Crédito motivo 01. La reversión financiera solo ocurre después de la
     * confirmación de SUNAT. Sin CPE aceptado conserva el flujo local actual.
     */
    public function cancel(
        Request $request,
        Payment $payment,
        CreditNoteService $creditNoteService
    ) {
        try {
            $payment->load(['details', 'invoices']);

            if ($payment->status === 'anulado') {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'El pago ya está anulado.',
                ], 400);
            }

            $sunatInvoices = $payment->invoices
                ->whereIn('document_type', ['receipt', 'invoice']);

            $acceptedInvoices = $sunatInvoices
                ->where('sunat_status', 'accepted')
                ->values();

            if ($acceptedInvoices->count() > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este pago tiene más de un comprobante SUNAT aceptado. ' .
                        'Debe revisarse manualmente antes de anular.',
                ], 422);
            }

            $pendingInvoice = $sunatInvoices
                ->firstWhere('sunat_status', 'pending');

            $creditNote = null;

            if ($acceptedInvoices->isNotEmpty()) {
                $reason = trim((string) $request->input('cancellation_reason'));

                if (mb_strlen($reason) < 5) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ingrese un motivo de anulación de al menos 5 caracteres.',
                    ], 422);
                }

                if (mb_strlen($reason) > 500) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El motivo de anulación no puede superar los 500 caracteres.',
                    ], 422);
                }

                /** @var Invoice $acceptedInvoice */
                $acceptedInvoice = $acceptedInvoices->first();

                $noteResult = $creditNoteService->annulAcceptedInvoice(
                    $acceptedInvoice,
                    $reason
                );

                if (! ($noteResult['success'] ?? false)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $noteResult['message'] ??
                            'No se pudo confirmar la Nota de Crédito con SUNAT.',
                    ], 422);
                }

                $creditNote = $noteResult['credit_note'] ?? null;
            } elseif ($pendingInvoice) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'El pago tiene un comprobante SUNAT pendiente (' .
                        $pendingInvoice->series . '-' . $pendingInvoice->number .
                        '). Revise su estado antes de anular el pago.',
                ], 409);
            }

            DB::transaction(function () use ($payment) {
                $lockedPayment = Payment::with('details')
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->status === 'anulado') {
                    return;
                }

                $this->performLocalCancellation($lockedPayment);
            });

            $message = 'Pago anulado correctamente.';

            if ($creditNote) {
                $message = 'Nota de Crédito ' . $creditNote->series . '-' .
                    $creditNote->number . ' aceptada por SUNAT. ' .
                    'Pago anulado correctamente.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'credit_note' => $creditNote ? [
                    'id' => $creditNote->id,
                    'series' => $creditNote->series,
                    'number' => $creditNote->number,
                ] : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al anular pago y/o comprobante.', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al anular pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conserva la lógica financiera de anulación que ya utilizaba el sistema.
     * Este método no realiza ninguna comunicación tributaria.
     */
    private function performLocalCancellation(Payment $payment): void
    {
        $payment->status = 'anulado';
        $payment->updated_by = Auth::id();
        $payment->save();

        foreach ($payment->details as $detail) {
            $schedule = PaymentSchedule::with('sale.lateFeeSetting')
                ->find($detail->payment_schedule_id);

            if (!$schedule) {
                continue;
            }

            $totalCapitalPaid = PaymentDetail::where(
                'payment_schedule_id',
                $schedule->id
            )
                ->whereHas('payment', function ($q) {
                    $q->where('status', 'activo');
                })
                ->sum('applied_amount');

            $totalLateFeePaid = Payment::where(
                'payment_schedule_id',
                $schedule->id
            )
                ->where('status', 'activo')
                ->sum('late_fee_paid');

            $totalPaid = $totalCapitalPaid + $totalLateFeePaid;

            $lateFee = $this->calculateLateFeeForSchedule($schedule);
            $schedule->late_fee = round($lateFee, 2);

            $realTotal = $schedule->total_amount + $schedule->late_fee;
            $remaining = $realTotal - $totalPaid;

            if ($remaining < 0) {
                $remaining = 0;
            }

            $schedule->remaining_balance = $remaining;

            if ($remaining <= 0) {
                $schedule->status = 'pagado';
            } elseif ($totalPaid > 0) {
                $schedule->status = 'parcial';
            } else {
                $schedule->status = 'pendiente';
            }

            $schedule->save();
        }

        $this->updateSaleAndLotStatus($payment->sale_id);
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
