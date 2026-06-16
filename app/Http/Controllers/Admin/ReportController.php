<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use App\Models\Company;
use App\Models\Project;
use App\Models\Block;

use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use App\Exports\PaymentsReportExport;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX REPORTES
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view('admin.reports.index');
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTE DE VENTAS
    |--------------------------------------------------------------------------
    */

    public function sales()
    {
        return view('admin.reports.sales');
    }

    public function salesList(Request $request)
    {


        $sales = Sale::with([
            'customer',
            'lot.block.project.company',
        ])->select('sales.*');
        if ($request->filled('block_id')) {
            $sales->whereHas(
                'lot',
                function ($q) use ($request) {
                    $q->where(
                        'block_id',
                        $request->block_id
                    );
                }
            );
        }

        if ($request->filled('sale_type')) {
            $sales->where(
                'sale_type',
                $request->sale_type
            );
        }
        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $sales->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $sales->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->filled('company_id')) {
            $sales->whereHas(
                'lot.block.project',
                function ($q) use ($request) {
                    $q->where('company_id', $request->company_id);
                }
            );
        }

        if ($request->filled('project_id')) {
            $sales->whereHas(
                'lot.block',
                function ($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                }
            );
        }

        return DataTables::of($sales)

            ->addColumn('customer_name', function ($sale) {
                return $sale->customer->full_name ?? '-';
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
                return $sale->lot->code ?? '-';
            })

            ->editColumn('sale_date', function ($sale) {
                return optional($sale->sale_date)
                    ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y')
                    : '-';
            })

            ->editColumn('sale_type', function ($sale) {

                if ($sale->sale_type == 'contado') {
                    return '<span class="badge badge-success">CONTADO</span>';
                }

                return '<span class="badge badge-primary">FINANCIADO</span>';
            })

            ->addColumn('total_price', function ($sale) {
                return 'S/ ' . number_format($sale->lot_price, 2);
            })

            ->editColumn('status', function ($sale) {

                switch ($sale->status) {

                    case 'activo':
                        return '<span class="badge badge-success">ACTIVO</span>';

                    case 'cancelado':
                        return '<span class="badge badge-danger">CANCELADO</span>';

                    case 'rescindido':
                        return '<span class="badge badge-warning">RESCINDIDO</span>';

                    case 'finalizado':
                        return '<span class="badge badge-primary">FINALIZADO</span>';

                    default:
                        return '<span class="badge badge-secondary">SIN ESTADO</span>';
                }
            })

            ->rawColumns([
                'sale_type',
                'status',
            ])

            ->make(true);
    }


    /*
|--------------------------------------------------------------------------
| FILTROS REPORTE DE VENTAS
|--------------------------------------------------------------------------
*/

    public function getCompanies()
    {
        return response()->json(
            Company::where('status', 1)
                ->orderBy('trade_name')
                ->get([
                    'id',
                    'trade_name'
                ])
        );
    }

    public function getProjects($companyId)
    {
        return response()->json(
            Project::where('company_id', $companyId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name'
                ])
        );
    }

    public function getBlocks($projectId)
    {
        return response()->json(
            Block::where('project_id', $projectId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name'
                ])
        );
    }

    public function exportSalesExcel(Request $request)
    {
        return Excel::download(
            new SalesReportExport($request->all()),
            'reporte_ventas_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportSalesPdf(Request $request)
    {
        $sales = Sale::with([
            'customer',
            'lot.block.project.company'
        ]);

        if ($request->filled('company_id')) {
            $sales->whereHas('lot.block.project', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('project_id')) {
            $sales->whereHas('lot.block', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('block_id')) {
            $sales->whereHas('lot', function ($q) use ($request) {
                $q->where('block_id', $request->block_id);
            });
        }

        if ($request->filled('sale_type')) {
            $sales->where('sale_type', $request->sale_type);
        }

        if ($request->filled('date_from')) {
            $sales->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $sales->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $sales->get();

        $totalPrecio = $sales->sum('lot_price');
        $totalPendiente = $sales->sum('balance_finance');

        $pdf = Pdf::loadView(
            'admin.reports.pdf.sales',
            [
                'sales' => $sales,
                'totalPrecio' => $totalPrecio,
                'totalPendiente' => $totalPendiente,
            ]
        );

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download(
            'reporte_ventas_' . now()->format('Ymd_His') . '.pdf'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | PLACEHOLDERS PARA LOS DEMÁS REPORTES
    |--------------------------------------------------------------------------
    */

    public function payments()
    {
        return view('admin.reports.payments');
    }

    public function paymentsList(Request $request)
    {
        $payments = Payment::with([
            'sale.customer',
            'sale.lot.block.project.company',
            'bank',
        ])->select('payments.*');

        /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

        if ($request->filled('company_id')) {
            $payments->whereHas('sale.lot.block.project', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('project_id')) {
            $payments->whereHas('sale.lot.block', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('block_id')) {
            $payments->whereHas('sale.lot', function ($q) use ($request) {
                $q->where('block_id', $request->block_id);
            });
        }

        if ($request->filled('date_from')) {
            $payments->whereDate(
                'payment_date',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $payments->whereDate(
                'payment_date',
                '<=',
                $request->date_to
            );
        }

        return DataTables::of($payments)

            ->addColumn('sale_code', function ($payment) {
                return optional($payment->sale)->sale_code ?? '-';
            })

            ->addColumn('customer_name', function ($payment) {
                return optional(
                    optional($payment->sale)->customer
                )->full_name ?? '-';
            })

            ->addColumn('company_name', function ($payment) {
                return optional(
                    optional(
                        optional(
                            optional(
                                optional($payment->sale)->lot
                            )->block
                        )->project
                    )->company
                )->trade_name ?? '-';
            })

            ->addColumn('project_name', function ($payment) {
                return optional(
                    optional(
                        optional(
                            optional($payment->sale)->lot
                        )->block
                    )->project
                )->name ?? '-';
            })

            ->addColumn('lot_name', function ($payment) {
                return optional(
                    optional($payment->sale)->lot
                )->code ?? '-';
            })

            ->editColumn('payment_date', function ($payment) {
                return $payment->payment_date
                    ? \Carbon\Carbon::parse(
                        $payment->payment_date
                    )->format('d/m/Y')
                    : '-';
            })

            ->editColumn('payment_type', function ($payment) {
                return strtoupper($payment->payment_type);
            })

            ->addColumn('amount_format', function ($payment) {
                return 'S/ ' . number_format(
                    $payment->amount,
                    2
                );
            })

            ->addColumn('payment_method_name', function ($payment) {
                return ucfirst($payment->payment_method);
            })

            ->editColumn('status', function ($payment) {

                switch ($payment->status) {

                    case 'activo':
                        return '<span class="badge badge-success">ACTIVO</span>';

                    case 'anulado':
                        return '<span class="badge badge-danger">ANULADO</span>';

                    default:
                        return '<span class="badge badge-secondary">'
                            . strtoupper($payment->status)
                            . '</span>';
                }
            })

            ->rawColumns([
                'status',
            ])

            ->make(true);
    }

    public function exportPaymentsExcel(Request $request)
    {
        return Excel::download(
            new PaymentsReportExport($request->all()),
            'reporte_pagos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportPaymentsPdf(Request $request)
    {
        $payments = Payment::with([
            'sale.customer',
            'sale.lot.block.project.company',
            'bank',
        ]);

        /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

        if ($request->filled('company_id')) {
            $payments->whereHas(
                'sale.lot.block.project',
                function ($q) use ($request) {
                    $q->where('company_id', $request->company_id);
                }
            );
        }

        if ($request->filled('project_id')) {
            $payments->whereHas(
                'sale.lot.block',
                function ($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                }
            );
        }

        if ($request->filled('block_id')) {
            $payments->whereHas(
                'sale.lot',
                function ($q) use ($request) {
                    $q->where('block_id', $request->block_id);
                }
            );
        }

        if ($request->filled('date_from')) {
            $payments->whereDate(
                'payment_date',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $payments->whereDate(
                'payment_date',
                '<=',
                $request->date_to
            );
        }

        $payments = $payments->get();

        // Totales
        $totalPagado = $payments->sum('amount');
        $totalMora = $payments->sum('late_fee_paid');
        $totalDescuento = $payments->sum('discount');

        $pdf = Pdf::loadView(
            'admin.reports.pdf.payments',
            [
                'payments'        => $payments,
                'totalPagado'     => $totalPagado,
                'totalMora'       => $totalMora,
                'totalDescuento'  => $totalDescuento,
            ]
        );

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download(
            'reporte_pagos_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    public function invoices()
    {
        return view('admin.reports.invoices');
    }

    public function collections()
    {
        return view('admin.reports.collections');
    }

    public function overdue()
    {
        return view('admin.reports.overdue');
    }
}
