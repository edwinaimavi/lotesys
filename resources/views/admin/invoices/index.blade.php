@extends('layouts.app')

@section('subtitle', 'Facturación Electrónica')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>

                <h1 class="mb-1 font-weight-bold text-dark">

                    <i class="fas fa-file-invoice-dollar text-primary"></i>
                    Facturación Electrónica

                </h1>

                <small class="text-muted">
                    Gestión de comprobantes electrónicos SUNAT.
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

                            Facturación Electrónica

                        </li>

                    </ol>

                </nav>

            </div>

        </div>

    </div>

    <div class="modal fade" id="apiResponseModal" tabindex="-1" role="dialog" aria-labelledby="apiResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title font-weight-bold" id="apiResponseModalLabel">
                            <i class="fas fa-code text-info mr-2"></i>Respuesta API
                        </h5>
                        <small id="apiInvoiceLabel" class="text-muted"></small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="apiResponseLoading" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                    </div>
                    <div id="apiResponseError" class="alert alert-danger d-none"></div>
                    <div id="apiResponseContent" class="d-none">
                        <div class="form-group mb-3">
                            <label for="apiAttemptSelect" class="font-weight-bold">Intento registrado</label>
                            <select id="apiAttemptSelect" class="form-control form-control-sm"></select>
                        </div>
                        <div id="apiAttemptDetails"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" id="copyApiResponse" class="btn btn-outline-info btn-sm">
                        <i class="far fa-copy mr-1"></i>Copiar respuesta
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@stop

@section('content_body')

    <div class="card border-0 shadow-lg rounded-xl">

        <div class="card-header bg-white border-0 pt-4 pb-2">

            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div>

                    <h5 class="mb-1 font-weight-bold text-dark">

                        <i class="fas fa-receipt text-primary"></i>
                        Lista de Comprobantes

                    </h5>

                    <small class="text-muted">
                        Facturas, boletas y documentos electrónicos emitidos.
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableInvoice" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>#</th>

                            <th>ID</th>

                            <th>TIPO</th>

                            <th>SERIE</th>

                            <th>NÚMERO</th>

                            <th>FECHA EMISIÓN</th>

                            <th>CLIENTE</th>

                            <th>TOTAL</th>

                            <th>ESTADO SUNAT</th>

                            <th width="180px">ACCIONES</th>

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

        #tableInvoice thead th {

            border: none !important;

            font-size: 13px;

            font-weight: 700;

            color: #555;

            padding: 15px;

            white-space: nowrap;

        }

        #tableInvoice tbody td {

            vertical-align: middle !important;

            padding: 14px;

            border-top: 1px solid #f1f1f1;

            font-size: 14px;

        }

        #tableInvoice tbody tr:hover {

            background: #fafafa;

            transition: .2s ease;

        }

        .breadcrumb {

            margin-bottom: 0;

        }

        .btn-primary {

            border-radius: 10px;

        }

        .card {

            overflow: hidden;

        }

        .badge-soft-success {

            background: rgba(40, 167, 69, .12);

            color: #28a745;

        }

        .badge-soft-danger {

            background: rgba(220, 53, 69, .12);

            color: #dc3545;

        }

        .badge-soft-warning {

            background: rgba(255, 193, 7, .12);

            color: #856404;

        }

        .badge-soft-info {

            background: rgba(23, 162, 184, .12);

            color: #17a2b8;

        }

        .api-code-block {
            max-height: 320px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            background: #1f2933;
            color: #e8eef5;
            border-radius: 8px;
            padding: 14px;
            font: 12px/1.5 Consolas, Monaco, monospace;
        }
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            invoiceList: "{{ route('admin.invoices.list') }}",

        }
    </script>

    @vite(['resources/js/pages/invoice.js'])
@endpush
