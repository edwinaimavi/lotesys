@extends('layouts.app')

@section('subtitle', 'Bancos')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>

                <h1 class="mb-1 font-weight-bold text-dark">

                    <i class="fas fa-university text-primary"></i>
                    Bancos

                </h1>

                <small class="text-muted">
                    Gestión de cuentas bancarias de la empresa.
                </small>

            </div>

            <div>

                <button class="btn btn-primary shadow-sm px-4" type="button" data-toggle="modal" data-target="#bankModal">

                    <i class="fas fa-plus-circle mr-1"></i>
                    Nuevo Banco

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

                            Bancos

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

                        <i class="fas fa-list text-primary"></i>
                        Lista de Bancos

                    </h5>

                    <small class="text-muted">
                        Cuentas bancarias registradas en el sistema
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableBank" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>#</th>

                            <th>ID</th>

                            <th>BANCO</th>

                            <th>MONEDA</th>

                            <th>N° CUENTA</th>

                            <th>CCI</th>

                            <th>TITULAR</th>

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
    @include('admin.banks.partials.modal')

    @include('admin.banks.partials.viewModal')

@stop

@push('css')
    <style>
        .rounded-xl {

            border-radius: 18px;

        }

        #tableBank thead th {

            border: none !important;
            font-size: 13px;
            font-weight: 700;
            color: #555;
            padding: 15px;
            white-space: nowrap;

        }

        #tableBank tbody td {

            vertical-align: middle !important;
            padding: 14px;
            border-top: 1px solid #f1f1f1;
            font-size: 14px;

        }

        #tableBank tbody tr:hover {

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
    </style>
@endpush

@push('js')
    <script>
        window.routes = {

            bankList: "{{ route('admin.banks.list') }}",

            storeBank: "{{ route('admin.banks.store') }}",

            updateBank: "{{ url('admin/banks') }}",

            deleteBank: "{{ url('admin/banks') }}",

        }
    </script>

    @vite(['resources/js/pages/bank.js'])
@endpush
