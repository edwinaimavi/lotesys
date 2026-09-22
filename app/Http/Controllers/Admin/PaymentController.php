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
use App\Models\PaymentReceipt;
use App\Models\Lot;
use Carbon\Carbon;
use App\Models\Holiday;

use App\Services\ApisPeruService;
use App\Services\CreditNoteService;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private function validatePaymentEvidence(Request $request): array
    {
        foreach (['origin_bank', 'origin_bank_other', 'operation_number'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:efectivo,transferencia,deposito,yape,plin'],
            'origin_bank' => ['exclude_unless:payment_method,transferencia', 'required', 'string', 'max:100'],
            'origin_bank_other' => ['exclude_unless:payment_method,transferencia', 'exclude_unless:origin_bank,Otra entidad', 'required', 'string', 'max:100'],
            'operation_number' => ['exclude_if:payment_method,efectivo', 'required', 'string', 'max:100'],
            'receipts' => ['nullable', 'array', 'max:10'],
            'receipts.*' => [
                'required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                function ($attribute, $file, $fail) {
                    if (!$file instanceof \Illuminate\Http\UploadedFile ||
                        !in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                        $fail('El comprobante debe ser JPG, PNG, WEBP o PDF.');
                    }
                },
            ],
        ], [
            'origin_bank.required' => 'Seleccione el banco de origen.',
            'origin_bank_other.required' => 'Ingrese el nombre de la entidad.',
            'operation_number.required' => 'Ingrese el N.º de operación / referencia.',
            'receipts.max' => 'Se permiten como máximo 10 comprobantes por pago.',
            'receipts.*.max' => 'Cada comprobante debe pesar como máximo 5 MB.',
        ]);

        return [
            'origin_bank' => ($data['origin_bank'] ?? null) === 'Otra entidad'
                ? $data['origin_bank_other'] : ($data['origin_bank'] ?? null),
            'operation_number' => $data['operation_number'] ?? null,
        ];
    }

    private function storeReceipts(Payment $payment, Request $request, array &$storedPaths): void
    {
        $files = $request->file('receipts', []);
        if ($payment->receipts()->count() + count($files) > 10) {
            throw ValidationException::withMessages(['receipts' => 'Se permiten como máximo 10 comprobantes por pago.']);
        }

        foreach ($files as $file) {
            $path = PaymentReceipt::storage()->putFile((string) $payment->id, $file);
            if (!$path) {
                throw new \RuntimeException('No se pudo almacenar el comprobante.');
            }
            $storedPaths[] = $path;
            $payment->receipts()->create([
                'disk' => PaymentReceipt::DISK,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function removeStoredReceipts(array $paths): void
    {
        foreach ($paths as $path) {
            PaymentReceipt::storage()->delete($path);
        }
    }

    public function evidence(Payment $payment)
    {
        return response()->json([
            'origin_bank' => $payment->origin_bank,
            'historical_bank' => $payment->origin_bank ? null : $payment->bank?->bank_name,
            'operation_number' => $payment->operation_number,
            'receipts' => $payment->receipts()->orderBy('id')->get()->map(fn ($receipt) => [
                'name' => $receipt->original_name,
                'mime_type' => $receipt->mime_type,
                'file_size' => $receipt->file_size,
                'url' => route('admin.payments.receipts.show', [$payment, $receipt]),
            ]),
        ]);
    }

    public function receipt(Payment $payment, PaymentReceipt $receipt)
    {
        abort_unless((int) $receipt->payment_id === (int) $payment->id, 404);
        abort_unless($receipt->disk === PaymentReceipt::DISK, 404);
        abort_unless(in_array($receipt->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true), 404);
        $storage = PaymentReceipt::storage();
        abort_unless($storage->exists($receipt->path), 404);

        return $storage->response($receipt->path, 'comprobante-' . $receipt->id . '.' . pathinfo($receipt->path, PATHINFO_EXTENSION), [
            'Content-Type' => $receipt->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], 'inline');
    }

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
            ->withCount('saleLots')
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
        // ENTIDADES DE ORIGEN INDEPENDIENTES DE LAS CUENTAS DE LA EMPRESA
        // ============================================

        $originBanks = config('payments.origin_banks');

        return view('admin.payments.index', compact(
            'sales',
            'paymentSchedules',
            'originBanks'
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
        $evidence = $this->validatePaymentEvidence($request);
        $storedPaths = [];
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
                'origin_bank'       => $evidence['origin_bank'],
                'operation_number'  => $evidence['operation_number'],
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

            $this->storeReceipts($payment, $request, $storedPaths);
            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Pago registrado correctamente.',

                'data' => $payment

            ], 201);
        } catch (ValidationException $e) {

            DB::rollBack();
            $this->removeStoredReceipts($storedPaths);

            throw $e;
        } catch (\Throwable $e) {

            DB::rollBack();
            $this->removeStoredReceipts($storedPaths);

            Log::error(
                'Error creating payment: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar el pago.'

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

        $evidence = $this->validatePaymentEvidence($request);
        $storedPaths = [];
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

            $payment = Payment::whereKey($id)->lockForUpdate()->firstOrFail();
            $payment->update(array_merge($data, $evidence));

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

            $this->storeReceipts($payment, $request, $storedPaths);
            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Pago actualizado correctamente.',

                'data' => $payment->fresh()

            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->removeStoredReceipts($storedPaths);
            throw $e;
        } catch (\Throwable $e) {

            DB::rollBack();

            $this->removeStoredReceipts($storedPaths);

            Log::error(
                'Error updating payment: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar el pago.'

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
        return app(\App\Services\PaymentScheduleLateFee::class)->calculate($schedule);
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
