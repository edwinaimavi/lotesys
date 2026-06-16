@extends('layouts.app')

@section('subtitle', 'Configuración de Mora')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>

                <h1 class="mb-1 font-weight-bold text-dark">

                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Configuración de Mora

                </h1>

                <small class="text-muted">
                    Gestión de parámetros financieros para cálculo automático de mora.
                </small>

            </div>

            <div>

                <button class="btn btn-primary shadow-sm px-4" type="button" data-toggle="modal"
                    data-target="#lateFeeSettingModal">

                    <i class="fas fa-plus-circle mr-1"></i>
                    Nueva Configuración

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

                            Configuración de Mora

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

                        <i class="fas fa-cogs text-danger"></i>
                        Lista de Configuraciones

                    </h5>

                    <small class="text-muted">
                        Parámetros globales para cálculo financiero de mora
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableLateFeeSetting" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>#</th>

                            <th>ID</th>

                            <th>DÍAS GRACIA</th>

                            <th>MORA DIARIA</th>

                            <th>MORA MÁXIMA</th>

                            <th>DOMINGOS</th>

                            <th>FERIADOS</th>

                            <th>ESTADO</th>

                            <th width="140px">ACCIONES</th>

                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- MODALS --}}
    @include('admin.lateFeeSettings.partials.modal')

    @include('admin.lateFeeSettings.partials.viewModal')

@stop

@push('css')
    <style>
        .rounded-xl {

            border-radius: 18px;

        }

        #tableLateFeeSetting thead th {

            border: none !important;

            font-size: 13px;

            font-weight: 700;

            color: #555;

            padding: 15px;

            white-space: nowrap;

        }

        #tableLateFeeSetting tbody td {

            vertical-align: middle !important;

            padding: 14px;

            border-top: 1px solid #f1f1f1;

            font-size: 14px;

        }

        #tableLateFeeSetting tbody tr:hover {

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
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            lateFeeSettingList: "{{ route('admin.lateFeeSettings.list') }}",

            storeLateFeeSetting: "{{ route('admin.lateFeeSettings.store') }}",

            updateLateFeeSetting: "{{ url('admin/lateFeeSettings') }}",

            deleteLateFeeSetting: "{{ url('admin/lateFeeSettings') }}",

        }
    </script>

    @vite(['resources/js/pages/lateFeeSetting.js'])
@endpush
