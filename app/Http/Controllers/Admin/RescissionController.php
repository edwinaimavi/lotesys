<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Rescission;
use App\Models\Lot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RescissionController extends Controller
{
    /**
     * Pantalla principal.
     */
    public function index()
    {
        return view('admin.rescissions.index');
    }

    /**
     * DataTable de contratos candidatos a rescisión.
     */
    public function list(Request $request)
    {
        $sales = Sale::with([
            'customer',
            'lot.block.project.company',
            'payments',
            'paymentSchedules'
        ])
            ->where('status', 'activo')
            ->where('sale_type', 'financiado')
            ->select('sales.*');

        return DataTables::of($sales)

            ->addColumn('customer_name', function ($sale) {
                return optional($sale->customer)->full_name ?? '-';
            })

            ->addColumn('company_name', function ($sale) {
                return optional(
                    optional(
                        optional(
                            optional($sale->lot)->block
                        )->project
                    )->company
                )->trade_name ?? '-';
            })

            ->addColumn('project_name', function ($sale) {
                return optional(
                    optional(
                        optional($sale->lot)->block
                    )->project
                )->name ?? '-';
            })

            ->addColumn('lot_name', function ($sale) {
                return optional($sale->lot)->code ?? '-';
            })

            ->editColumn('sale_date', function ($sale) {
                return $sale->sale_date
                    ? Carbon::parse($sale->sale_date)->format('d/m/Y')
                    : '-';
            })

            ->addColumn('overdue_installments', function ($sale) {

                return $sale->paymentSchedules
                    ->where('status', 'pendiente')
                    ->where('due_date', '<', now()->toDateString())
                    ->count();
            })

            ->addColumn('amount_paid', function ($sale) {

                return 'S/ ' . number_format(
                    $sale->payments->where('status', 'activo')->sum('amount'),
                    2
                );
            })

            ->editColumn('status', function ($sale) {

                $overdue = $sale->paymentSchedules
                    ->where('status', 'pendiente')
                    ->where('due_date', '<', now()->toDateString())
                    ->count();

                if ($overdue >= 3) {
                    return '<span class="badge badge-danger">CANDIDATO</span>';
                }

                if ($overdue > 0) {
                    return '<span class="badge badge-warning">EN MORA</span>';
                }

                return '<span class="badge badge-success">AL DÍA</span>';
            })

            ->addColumn('actions', function ($sale) {

                $overdue = $sale->paymentSchedules
                    ->where('status', 'pendiente')
                    ->where('due_date', '<', now()->toDateString())
                    ->count();

                if ($overdue >= 3) {
                    return '
                        <button
                            class="btn btn-danger btn-sm btnRescind"
                            data-id="' . $sale->id . '">
                            <i class="fas fa-file-signature"></i>
                            Rescindir
                        </button>
                    ';
                }

                return '<span class="text-muted">Sin acciones</span>';
            })

            ->rawColumns([
                'status',
                'actions'
            ])

            ->make(true);
    }

    /**
     * Devuelve la información para llenar el modal.
     */
    public function show($sale)
    {
        $sale = Sale::with([
            'customer',
            'lot.block.project.company',
            'payments',
            'paymentSchedules'
        ])->findOrFail($sale);

        $overdueInstallments = $sale->paymentSchedules
            ->where('status', 'pendiente')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $amountPaid = $sale->payments
            ->where('status', 'activo')
            ->sum('amount');

        return response()->json([
            'id' => $sale->id,
            'sale_code' => $sale->sale_code,
            'customer_name' => optional($sale->customer)->full_name ?? '-',
            'company_name' => optional(
                optional(
                    optional(
                        optional($sale->lot)->block
                    )->project
                )->company
            )->trade_name ?? '-',
            'project_name' => optional(
                optional(
                    optional($sale->lot)->block
                )->project
            )->name ?? '-',
            'lot_name' => optional($sale->lot)->code ?? '-',
            'sale_date' => optional($sale->sale_date)
                ? Carbon::parse($sale->sale_date)->format('d/m/Y')
                : '-',
            'overdue_installments' => $overdueInstallments,
            'amount_paid' => $amountPaid,
            'balance_finance' => $sale->balance_finance,
        ]);
    }

    /**
     * Registrar rescisión.
     * (Lo implementaremos después).
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_id'                => 'required|exists:sales,id',
            'rescission_date'        => 'required|date',
            'reason'                 => 'required|string|max:255',
            'overdue_installments'   => 'required|integer|min:0',
            'amount_paid'            => 'required|numeric|min:0',
            'penalty_amount'         => 'nullable|numeric|min:0',
            'observation'            => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {

            $sale = Sale::with('lot')->findOrFail($request->sale_id);

            // Evitar doble rescisión
            if ($sale->status == 'rescindido') {
                return response()->json([
                    'message' => 'Esta venta ya fue rescindida.'
                ], 422);
            }

            // Registrar rescisión
            Rescission::create([
                'sale_id'              => $sale->id,
                'rescission_date'      => $request->rescission_date,
                'reason'               => $request->reason,
                'overdue_installments' => $request->overdue_installments,
                'amount_paid'          => $request->amount_paid,
                'penalty_amount'       => $request->penalty_amount ?? 0,
                'observation'          => $request->observation,
                'user_id'              => Auth::id(),
            ]);

            // Cambiar estado de la venta
            $sale->status = 'rescindido';
            $sale->save();

            // Liberar el lote
            if ($sale->lot) {
                $sale->lot->status = 'disponible';
                $sale->lot->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'La rescisión fue registrada correctamente.'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al registrar la rescisión.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
