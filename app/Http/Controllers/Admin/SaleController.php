<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LateFeeSetting;
use App\Models\Lot;
use App\Models\Sale;
use App\Models\SaleLot;
use Illuminate\Validation\ValidationException;
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
        $lots = Lot::with(['project.company', 'block'])
            ->orderBy('code')
            ->get();

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
            'lot.project.company',
            'lot.block',
            'saleLots.lot.project.company',
            'saleLots.lot.block',
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

            ->addColumn('company', function ($sale) {

                $company = $sale->lot?->project?->company;

                return $company?->trade_name
                    ?: ($company?->business_name ?? '—');
            })

            ->addColumn('project', function ($sale) {

                return $sale->lot?->project?->name ?? '—';
            })

            ->addColumn('lot_location', function ($sale) {

                $lots = $this->getSaleLotsForDisplay($sale);

                if ($lots->isEmpty()) {
                    return '—';
                }

                if ($lots->count() === 1) {
                    $lot = $lots->first();

                    return ($lot->block?->name ?? '—')
                        . ' / LT '
                        . ($lot->number ?? '—');
                }

                $summary = $lots
                    ->map(function ($lot) {
                        return ($lot->block?->name ?? '—')
                            . '/LT '
                            . ($lot->number ?? '—');
                    })
                    ->implode(', ');

                return $lots->count() . ' lotes · ' . $summary;
            })

            ->addColumn('lot_code', function ($sale) {

                $lots = $this->getSaleLotsForDisplay($sale);

                if ($lots->isEmpty()) {
                    return '—';
                }

                if ($lots->count() === 1) {
                    return $lots->first()?->code ?? '—';
                }

                return $lots
                    ->pluck('code')
                    ->filter()
                    ->implode(', ');
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
     * LOTES DISPONIBLES PARA CREAR O EDITAR UNA VENTA
     */
    public function availableLots(Request $request)
    {
        $data = $request->validate([
            'selected_lot_id' => [
                'nullable',
                'integer',
                'exists:lots,id'
            ]
        ]);

        $selectedLotId = $data['selected_lot_id'] ?? null;

        $lots = Lot::with(['project.company', 'block'])
            ->where(function ($query) use ($selectedLotId) {
                $query->where('status', 'disponible');

                if ($selectedLotId) {
                    $query->orWhere('id', $selectedLotId);
                }
            })
            ->orderBy('code')
            ->get();

        return response()->json(
            $lots->map(function ($lot) {
                $company = $lot->project?->company;
                $companyName = $company?->trade_name
                    ?: ($company?->business_name ?? 'Sin empresa');
                $projectName = $lot->project?->name ?? 'Sin proyecto';
                $blockName = $lot->block?->name ?? 'Sin manzana';
                $lotNumber = $lot->number ?? '—';

                return [
                    'id' => $lot->id,
                    'text' => $companyName
                        . ' · ' . $projectName
                        . ' · ' . $blockName
                        . ' · Lote ' . $lotNumber
                        . ' · ' . $lot->code,
                    'company_id' => $company?->id,
                    'company' => $companyName,
                    'project_id' => $lot->project_id,
                    'project' => $projectName,
                    'block_id' => $lot->block_id,
                    'block' => $blockName,
                    'lot_number' => $lotNumber,
                    'lot_code' => $lot->code,
                    'area' => $lot->area,
                    'unit_measure' => $lot->unit_measure,
                    'cash_price' => $lot->cash_price,
                    'financed_price' => $lot->financed_price
                ];
            })->values()
        );
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
            ],

            'is_legacy_sale' => [
                'nullable',
                'boolean'
            ],

            'collection_rules_start_date' => [
                'required_if:is_legacy_sale,1',
                'nullable',
                'date'
            ],

            'legacy_observation' => [
                'nullable',
                'string'
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

            $data['is_legacy_sale'] = $request->boolean('is_legacy_sale');

            if (!$data['is_legacy_sale']) {
                $data['collection_rules_start_date'] = null;
                $data['legacy_observation'] = null;
            }

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
     * STORE MULTIPLE
     *
     * Registra una sola venta y un solo cronograma para varios lotes.
     * sales.lot_id se conserva como lote principal para mantener
     * compatibilidad con los módulos que todavía trabajan con un lote.
     */
    public function storeMultiple(Request $request)
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

            'lot_ids' => [
                'required',
                'array',
                'min:2'
            ],

            'lot_ids.*' => [
                'required',
                'integer',
                'distinct',
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

            'initial_payment' => [
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
            ],

            'is_legacy_sale' => [
                'nullable',
                'boolean'
            ],

            'collection_rules_start_date' => [
                'required_if:is_legacy_sale,1',
                'nullable',
                'date'
            ],

            'legacy_observation' => [
                'nullable',
                'string'
            ],
        ]);

        try {
            DB::beginTransaction();

            $requestedLotIds = collect($data['lot_ids'])
                ->map(fn ($id) => (int) $id)
                ->values();

            $lotsById = Lot::with([
                'project.company',
                'block'
            ])
                ->whereIn('id', $requestedLotIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lotsById->count() !== $requestedLotIds->count()) {
                throw ValidationException::withMessages([
                    'lot_ids' => 'Uno o más lotes seleccionados ya no están disponibles.'
                ]);
            }

            $lots = $requestedLotIds
                ->map(fn ($id) => $lotsById->get($id))
                ->filter()
                ->values();

            $projectIds = $lots
                ->pluck('project_id')
                ->unique()
                ->values();

            if ($projectIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'lot_ids' => 'Todos los lotes de una venta múltiple deben pertenecer al mismo proyecto.'
                ]);
            }

            $notAvailable = $lots
                ->filter(fn ($lot) => $lot->status !== 'disponible');

            if ($notAvailable->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'lot_ids' => 'Uno o más lotes seleccionados dejaron de estar disponibles.'
                ]);
            }

            $hasActiveSimpleSale = Sale::whereIn('lot_id', $requestedLotIds)
                ->where('status', 'activo')
                ->exists();

            $hasActiveMultipleSale = SaleLot::whereIn('lot_id', $requestedLotIds)
                ->whereHas('sale', function ($query) {
                    $query->where('status', 'activo');
                })
                ->exists();

            if ($hasActiveSimpleSale || $hasActiveMultipleSale) {
                throw ValidationException::withMessages([
                    'lot_ids' => 'Uno o más lotes ya pertenecen a una venta activa.'
                ]);
            }

            $isCashSale = $data['sale_type'] === 'contado';

            $lotPrices = $lots->mapWithKeys(function ($lot) use ($isCashSale) {
                $price = $isCashSale
                    ? (float) $lot->cash_price
                    : (float) $lot->financed_price;

                return [
                    $lot->id => round($price, 2)
                ];
            });

            $totalPrice = round((float) $lotPrices->sum(), 2);
            $initialPayment = round((float) $data['initial_payment'], 2);

            if ($initialPayment - $totalPrice > 0.01) {
                throw ValidationException::withMessages([
                    'initial_payment' => 'La inicial no puede superar el precio total de los lotes.'
                ]);
            }

            if ($isCashSale) {
                $initialPayment = 0;
                $balanceFinance = $totalPrice;
                $installmentsCount = 1;
                $paymentMode = 'automatico';
                $customPayment = null;
                $monthlyPayment = $totalPrice;
                $interestRate = 0;
                $firstPaymentDate = $data['sale_date'];
                $paymentDay = Carbon::parse($data['sale_date'])->day;
            } else {
                if (empty($data['first_payment_date']) || empty($data['payment_day'])) {
                    throw ValidationException::withMessages([
                        'first_payment_date' => 'Debe indicar la fecha del primer pago.'
                    ]);
                }

                $balanceFinance = round($totalPrice - $initialPayment, 2);
                $installmentsCount = (int) $data['installments_count'];
                $paymentMode = $data['payment_mode'];
                $customPayment = $paymentMode === 'personalizado'
                    ? round((float) ($data['custom_payment'] ?? 0), 2)
                    : null;

                if ($paymentMode === 'personalizado' && $customPayment <= 0) {
                    throw ValidationException::withMessages([
                        'custom_payment' => 'Debe ingresar una cuota personalizada válida.'
                    ]);
                }

                $monthlyPayment = $paymentMode === 'personalizado'
                    ? $customPayment
                    : round($balanceFinance / max($installmentsCount, 1), 2);
                $interestRate = round((float) ($data['interest_rate'] ?? 0), 2);
                $firstPaymentDate = $data['first_payment_date'];
                $paymentDay = (int) $data['payment_day'];
            }

            $isLegacySale = $request->boolean('is_legacy_sale');

            $saleData = [
                'customer_id' => $data['customer_id'],
                // Primer lote = lote principal/referencial para compatibilidad.
                'lot_id' => $lots->first()->id,
                'sale_type' => $data['sale_type'],
                'sale_code' => $data['sale_code'],
                'sale_date' => $data['sale_date'],
                // En una venta múltiple lot_price representa el total de todos los lotes.
                'lot_price' => $totalPrice,
                'initial_payment' => $initialPayment,
                'balance_finance' => $balanceFinance,
                'installments_count' => $installmentsCount,
                'payment_mode' => $paymentMode,
                'custom_payment' => $customPayment,
                'monthly_payment' => $monthlyPayment,
                'interest_rate' => $interestRate,
                'first_payment_date' => $firstPaymentDate,
                'payment_day' => $paymentDay,
                'late_fee_setting_id' => $data['late_fee_setting_id'] ?? null,
                'status' => $data['status'],
                'is_legacy_sale' => $isLegacySale,
                'collection_rules_start_date' => $isLegacySale
                    ? ($data['collection_rules_start_date'] ?? null)
                    : null,
                'legacy_observation' => $isLegacySale
                    ? ($data['legacy_observation'] ?? null)
                    : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $sale = Sale::create($saleData);

            foreach ($lots as $index => $lot) {
                SaleLot::create([
                    'sale_id' => $sale->id,
                    'lot_id' => $lot->id,
                    'sale_price' => $lotPrices[$lot->id],
                    'is_primary' => $index === 0,
                ]);
            }

            $this->generatePaymentSchedules($sale, $saleData);

            $lotStatus = (float) $balanceFinance <= 0
                ? 'vendido'
                : 'separado';

            foreach ($lots as $lot) {
                $lot->status = $lotStatus;
                $lot->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Venta múltiple registrada correctamente.',
                'data' => $sale->fresh([
                    'saleLots.lot.project.company',
                    'saleLots.lot.block'
                ])
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error(
                'Error creating multi-lot sale: ' . $e->getMessage()
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar la venta múltiple.',
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

        if ($sale->saleLots()->count() > 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'La edición de ventas múltiples se realizará desde su flujo específico.'
            ], 422);
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
            ],

            'is_legacy_sale' => [
                'nullable',
                'boolean'
            ],

            'collection_rules_start_date' => [
                'required_if:is_legacy_sale,1',
                'nullable',
                'date'
            ],

            'legacy_observation' => [
                'nullable',
                'string'
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

            $data['is_legacy_sale'] = $request->boolean('is_legacy_sale');

            if (!$data['is_legacy_sale']) {
                $data['collection_rules_start_date'] = null;
                $data['legacy_observation'] = null;
            }

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
            'lot.project.company',
            'lot.block',
            'saleLots.lot.project.company',
            'saleLots.lot.block',
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
            $schedule->setRelation('sale', $sale);

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

            'lot_summary' => $this->formatSaleLotsSummary($sale),

        ]);
    }
    /**
     * DELETE
     */


    private function getSaleLotsForDisplay(Sale $sale)
    {
        if (! $sale->relationLoaded('saleLots')) {
            $sale->load([
                'saleLots.lot.project.company',
                'saleLots.lot.block'
            ]);
        }

        $lots = $sale->saleLots
            ->sortByDesc('is_primary')
            ->map(fn ($saleLot) => $saleLot->lot)
            ->filter()
            ->values();

        if ($lots->isEmpty() && $sale->lot) {
            $lots = collect([$sale->lot]);
        }

        return $lots;
    }

    private function formatSaleLotsSummary(Sale $sale): string
    {
        $lots = $this->getSaleLotsForDisplay($sale);

        if ($lots->isEmpty()) {
            return '—';
        }

        return $lots
            ->map(function ($lot) {
                return ($lot->block?->name ?? '—')
                    . ' · Lote '
                    . ($lot->number ?? '—')
                    . ' · '
                    . ($lot->code ?? '—');
            })
            ->implode(' | ');
    }


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
                        ->addMonthsNoOverflow($i)
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
                        ->addMonthsNoOverflow($i)
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
        $dueDate = $schedule->getEffectiveDueDate();

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
            // RESTAURAR LOTE(S)
            // =====================================================

            $sale->loadMissing('saleLots');

            $lotIds = $sale->saleLots
                ->pluck('lot_id')
                ->filter()
                ->values();

            if ($lotIds->isEmpty() && $sale->lot_id) {
                $lotIds = collect([$sale->lot_id]);
            }

            Lot::whereIn('id', $lotIds)
                ->update(['status' => 'disponible']);

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
