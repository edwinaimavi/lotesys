@extends('layouts.app')

@section('subtitle', 'Reportes')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>
                <h1 class="mb-1 font-weight-bold text-dark">
                    <i class="fas fa-chart-line text-primary"></i>
                    Reportes
                </h1>

                <small class="text-muted">
                    Consultas, estadísticas y reportes generales del sistema.
                </small>
            </div>

        </div>

        <div class="row">
            <div class="col-12">

                <nav aria-label="breadcrumb">

                    <ol class="breadcrumb bg-white shadow-sm rounded-pill px-3 py-2">

                        <li class="breadcrumb-item">
                            <a href="{{ route('home') }}" class="text-decoration-none">
                                <i class="fas fa-house-user"></i>
                                Home
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Reportes
                        </li>

                    </ol>

                </nav>

            </div>
        </div>

    </div>

@stop

@section('content_body')

    <div class="row">

        {{-- REPORTE DE VENTAS --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="{{ route('admin.reports.sales') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-xl card-report h-100">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-signature fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">
                            Reporte de Ventas
                        </h5>
                        <p class="text-muted mb-0">
                            Consulta de ventas realizadas por fechas,
                            proyectos y asesores.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- REPORTE DE PAGOS --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="{{ route('admin.reports.payments') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-xl card-report h-100">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                        <h5 class="font-weight-bold text-dark">
                            Reporte de Pagos
                        </h5>
                        <p class="text-muted mb-0">
                            Historial de pagos registrados y métodos
                            de pago utilizados.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- REPORTE DE COMPROBANTES --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="{{ route('admin.reports.invoices') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-xl card-report h-100">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-invoice-dollar fa-3x text-danger mb-3"></i>
                        <h5 class="font-weight-bold text-dark">
                            Reporte de Comprobantes
                        </h5>
                        <p class="text-muted mb-0">
                            Boletas, facturas y notas de venta
                            emitidas.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- REPORTE DE COBRANZA --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="{{ route('admin.reports.collections') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-xl card-report h-100">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-holding-usd fa-3x text-warning mb-3"></i>
                        <h5 class="font-weight-bold text-dark">
                            Reporte de Cobranza
                        </h5>
                        <p class="text-muted mb-0">
                            Resumen de cuotas cobradas y pendientes
                            por proyecto.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- REPORTE DE MOROSIDAD --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <a href="{{ route('admin.reports.overdue') }}" class="text-decoration-none">
                <div class="card border-0 shadow-lg rounded-xl card-report h-100">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-secondary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">
                            Reporte de Morosidad
                        </h5>
                        <p class="text-muted mb-0">
                            Clientes con cuotas vencidas y pagos
                            pendientes.
                        </p>
                    </div>
                </div>
            </a>
        </div>

    </div>

@stop

@push('css')
    <style>
        .rounded-xl {
            border-radius: 18px;
        }

        .breadcrumb {
            margin-bottom: 0;
        }

        .card-report {
            transition: .25s ease;
            cursor: pointer;
        }

        .card-report:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, .12) !important;
            background: #fafafa;
        }

        .card-report i {
            transition: .25s ease;
        }

        .card-report:hover i {
            transform: scale(1.08);
        }
    </style>
@endpush

@push('js')
    <script>
        window.routes = {
            reportSales: "{{ route('admin.reports.sales') }}",
            reportPayments: "{{ route('admin.reports.payments') }}",
            reportInvoices: "{{ route('admin.reports.invoices') }}",
            reportCollections: "{{ route('admin.reports.collections') }}",
            reportOverdue: "{{ route('admin.reports.overdue') }}"
        };
    </script>

    {{-- Cuando implementemos filtros o gráficos --}}
    @vite(['resources/js/pages/report.js'])
@endpush
