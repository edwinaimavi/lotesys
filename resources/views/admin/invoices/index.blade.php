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

            <div>

                <button class="btn btn-primary shadow-sm px-4" type="button" data-toggle="modal" data-target="#invoiceModal">

                    <i class="fas fa-plus-circle mr-1"></i>
                    Nuevo Comprobante

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

                        <li class="breadcrumb-item active">

                            Facturación Electrónica

                        </li>

                    </ol>

                </nav>

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

    {{-- MODALS --}}
 @include('admin.invoices.partials.modal')

   {{--. @include('admin.invoices.partials.viewModal')
 --}}
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
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            invoiceList: "{{ route('admin.invoices.list') }}",

            storeInvoice: "{{ route('admin.invoices.store') }}",

            updateInvoice: "{{ url('admin/invoices') }}",

            deleteInvoice: "{{ url('admin/invoices') }}",

            sendSunat: "{{ url('admin/invoices/send-sunat') }}",

            downloadPdf: "{{ url('admin/invoices/pdf') }}",

            downloadXml: "{{ url('admin/invoices/xml') }}",

            downloadCdr: "{{ url('admin/invoices/cdr') }}",

        }
    </script>

    @vite(['resources/js/pages/invoice.js'])
@endpush
