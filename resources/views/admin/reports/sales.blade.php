@extends('layouts.app')

@section('subtitle', 'Reporte de Ventas')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>
                <h1 class="mb-1 font-weight-bold text-dark">
                    <i class="fas fa-chart-line text-primary"></i>
                    Reporte de Ventas
                </h1>

                <small class="text-muted">
                    Consulta y análisis de las ventas registradas en el sistema.
                </small>
            </div>

            <div class="d-flex">

                <button class="btn btn-success shadow-sm px-4 mr-2" type="button" id="btnExportExcel">
                    <i class="fas fa-file-excel mr-1"></i>
                    Exportar Excel
                </button>

                <button class="btn btn-danger shadow-sm px-4" type="button" id="btnExportPdf">
                    <i class="fas fa-file-pdf mr-1"></i>
                    Exportar PDF
                </button>

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

                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.reports.index') }}" class="text-decoration-none">
                                Reportes
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Reporte de Ventas
                        </li>

                    </ol>

                </nav>

            </div>
        </div>

    </div>

@stop

@section('content_body')

    {{-- FILTROS --}}
    <div class="card border-0 shadow-sm rounded-xl mb-4">

        <div class="card-header bg-white border-0 pb-0 pt-4">
            <h5 class="font-weight-bold mb-1">
                <i class="fas fa-filter text-primary"></i>
                Filtros de Búsqueda
            </h5>
            <small class="text-muted">
                Filtre las ventas por empresa, proyecto y rango de fechas.
            </small>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Empresa
                    </label>
                    <select id="company_id" class="form-control">
                        <option value="">-- Todas --</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Proyecto
                    </label>
                    <select id="project_id" class="form-control">
                        <option value="">-- Todos --</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Manzana
                    </label>
                    <select id="block_id" class="form-control">
                        <option value="">-- Todas --</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Tipo Venta
                    </label>
                    <select id="sale_type" class="form-control">
                        <option value="">-- Todos --</option>
                        <option value="contado">Contado</option>
                        <option value="financiado">Financiado</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Desde
                    </label>
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">
                        Hasta
                    </label>
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-md-12 text-right mt-2">

                    <button type="button" id="btnSearch" class="btn btn-primary shadow-sm px-4">

                        <i class="fas fa-search mr-1"></i>
                        Buscar

                    </button>

                </div>

            </div>

        </div>

    </div>

    {{-- TABLA --}}
    <div class="card border-0 shadow-lg rounded-xl">

        <div class="card-header bg-white border-0 pt-4 pb-2">

            <div>

                <h5 class="mb-1 font-weight-bold text-dark">
                    <i class="fas fa-list text-primary"></i>
                    Ventas Registradas
                </h5>

                <small class="text-muted">
                    Resultados de acuerdo a los filtros seleccionados.
                </small>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableSalesReport" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>
                            <th>ID</th>
                            <th>CÓDIGO</th>
                            <th>FECHA</th>
                            <th>CLIENTE</th>
                            <th>EMPRESA</th>
                            <th>PROYECTO</th>
                            <th>LOTE</th>
                            <th>TIPO</th>
                            <th>PRECIO LOTE</th>
                            <th>ESTADO</th>
                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

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

        .card {
            overflow: hidden;
        }

        .btn-primary,
        .btn-success,
        .btn-danger {
            border-radius: 10px;
        }

        #tableSalesReport thead th {
            border: none !important;
            font-size: 13px;
            font-weight: 700;
            color: #555;
            padding: 15px;
            white-space: nowrap;
        }

        #tableSalesReport tbody td {
            vertical-align: middle !important;
            padding: 14px;
            border-top: 1px solid #f1f1f1;
            font-size: 14px;
        }

        #tableSalesReport tbody tr:hover {
            background: #fafafa;
            transition: .2s ease;
        }

        .form-control {
            border-radius: 10px;
        }
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            salesReportList: "{{ route('admin.reports.sales.list') }}",

            reportCompanies: "{{ route('admin.reports.companies') }}",

            reportProjects: "{{ url('admin/reports/projects') }}/:companyId",

            reportBlocks: "{{ url('admin/reports/blocks') }}/:projectId",

            // Las usaremos después
            salesReportExcel: "{{ route('admin.reports.sales.excel') }}",
             salesReportPdf: "{{ route('admin.reports.sales.pdf') }}",

            

        };
    </script>

    @vite(['resources/js/pages/report.js'])
@endpush
