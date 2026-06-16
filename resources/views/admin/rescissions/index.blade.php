@extends('layouts.app')

@section('subtitle', 'Rescisión de Contratos')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>

                <h1 class="mb-1 font-weight-bold text-dark">

                    <i class="fas fa-file-signature text-danger"></i>
                    Rescisión de Contratos

                </h1>

                <small class="text-muted">
                    Gestión de contratos rescindidos y contratos candidatos a rescisión.
                </small>

            </div>

            <div>

                {{--      <a href="#" class="btn btn-outline-danger shadow-sm px-4" id="rescissionModal">

                    <i class="fas fa-history mr-1"></i>
                    Historial de Rescisiones

                </a> --}}

                {{-- <button class="btn btn-danger shadow-sm px-4" type="button" data-toggle="modal" data-target="#rescissionModal">

                    <i class="fas fa-history mr-1"></i>
                    Historial de Rescisiones

                </button> --}}

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

                            Rescisión de Contratos

                        </li>

                    </ol>

                </nav>

            </div>

        </div>

    </div>

@stop

@section('content_body')

    {{-- ALERTA --}}
    <div class="alert alert-warning shadow-sm border-0 rounded-lg mb-4">

        <div class="d-flex align-items-center">

            <i class="fas fa-exclamation-circle fa-2x mr-3"></i>

            <div>

                <strong>Importante:</strong><br>

                Este módulo permite registrar la rescisión definitiva de un contrato.
                Al confirmar la operación el sistema:
                <ul class="mb-0 mt-2">
                    <li>Cambiará el estado de la venta a <strong>RESCINDIDO</strong>.</li>
                    <li>Liberará el lote, dejándolo nuevamente <strong>DISPONIBLE</strong>.</li>
                    <li>Registrará el historial de rescisión para fines de auditoría.</li>
                </ul>

            </div>

        </div>

    </div>

    {{-- TABLA --}}
    <div class="card border-0 shadow-lg rounded-xl">

        <div class="card-header bg-white border-0 pt-4 pb-2">

            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div>

                    <h5 class="mb-1 font-weight-bold text-dark">

                        <i class="fas fa-list-alt text-danger"></i>
                        Contratos Candidatos a Rescisión

                    </h5>

                    <small class="text-muted">
                        Ventas activas que presentan cuotas vencidas y pueden ser evaluadas para rescisión.
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableRescissions" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>ID</th>

                            <th>CÓDIGO</th>

                            <th>CLIENTE</th>

                            <th>EMPRESA</th>

                            <th>PROYECTO</th>

                            <th>LOTE</th>

                            <th>FECHA VENTA</th>

                            <th>CUOTAS VENCIDAS</th>

                            <th>MONTO PAGADO</th>

                            <th>ESTADO</th>

                            <th width="180px">ACCIONES</th>

                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- Modal de rescisión (lo crearemos después) --}}
    @include('admin.rescissions.partials.modal')

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

        .btn-danger,
        .btn-outline-danger {
            border-radius: 10px;
        }

        #tableRescissions thead th {
            border: none !important;
            font-size: 13px;
            font-weight: 700;
            color: #555;
            padding: 15px;
            white-space: nowrap;
        }

        #tableRescissions tbody td {
            vertical-align: middle !important;
            padding: 14px;
            border-top: 1px solid #f1f1f1;
            font-size: 14px;
        }

        #tableRescissions tbody tr:hover {
            background: #fafafa;
            transition: .2s ease;
        }

        .badge-soft-success {
            background: rgba(40, 167, 69, .12);
            color: #28a745;
        }

        .badge-soft-warning {
            background: rgba(255, 193, 7, .15);
            color: #d39e00;
        }

        .badge-soft-danger {
            background: rgba(220, 53, 69, .12);
            color: #dc3545;
        }

        .alert ul {
            padding-left: 18px;
        }
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            rescissionList: "{{ route('admin.rescissions.list') }}",

            storeRescission: "{{ route('admin.rescissions.store') }}",

            showRescission: "{{ url('admin/rescissions') }}",



        };
    </script>

    @vite(['resources/js/pages/rescission.js'])
@endpush
