@extends('layouts.app')

@section('subtitle', 'Lots')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>
                <h1 class="mb-1 font-weight-bold text-dark">
                    <i class="fas fa-map text-primary"></i>
                    Lotes
                </h1>

                <small class="text-muted">
                    Gestión y administración de lotes inmobiliarios.
                </small>
            </div>

            <div>
                <button class="btn btn-primary shadow-sm px-4" type="button" data-toggle="modal" data-target="#lotModal">

                    <i class="fas fa-plus-circle mr-1"></i>
                    Nuevo Lote
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
                            Lotes
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
                        Lista de Lotes
                    </h5>

                    <small class="text-muted">
                        Lotes registrados en el sistema
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <!-- FILTROS -->
            <div class="row mb-4">

                {{-- EMPRESA --}}
                <div class="col-md-3 mb-2">

                    <label class="font-weight-bold">
                        Empresa
                    </label>

                    <select id="filter_company" class="form-control">

                        <option value="">
                            Todas
                        </option>

                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">
                                {{ $company->business_name }}
                            </option>
                        @endforeach

                    </select>

                </div>

                {{-- PROYECTO --}}
                <div class="col-md-3 mb-2">

                    <label class="font-weight-bold">
                        Proyecto
                    </label>

                    <select id="filter_project" class="form-control">

                        <option value="">
                            Todos
                        </option>

                    </select>

                </div>

                {{-- MANZANA --}}
                <div class="col-md-3 mb-2">

                    <label class="font-weight-bold">
                        Manzana
                    </label>

                    <select id="filter_block" class="form-control">

                        <option value="">
                            Todas
                        </option>

                    </select>

                </div>

                {{-- LOTE --}}
                <div class="col-md-3 mb-2">

                    <label class="font-weight-bold">
                        Lote
                    </label>

                    <input type="text" id="filter_lot" class="form-control" placeholder="Ej: 01">

                </div>

            </div>

            <div class="table-responsive">

                <table id="tableLot" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>#</th>

                            <th>ID</th>

                            <th>PROYECTO</th>

                            <th>MANZANA</th>

                            <th>CÓDIGO</th>

                            <th>LOTE</th>

                            <th>ÁREA</th>

                            <th>PRECIO CONTADO</th>

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
    @include('admin.lots.partials.modal')

    @include('admin.lots.partials.viewModal')

@stop

@push('css')
    <style>
        .rounded-xl {
            border-radius: 18px;
        }

        #tableLot thead th {
            border: none !important;
            font-size: 13px;
            font-weight: 700;
            color: #555;
            padding: 15px;
            white-space: nowrap;
        }

        #tableLot tbody td {
            vertical-align: middle !important;
            padding: 14px;
            border-top: 1px solid #f1f1f1;
            font-size: 14px;
        }

        #tableLot tbody tr:hover {
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

            lotList: "{{ route('admin.lots.list') }}",

            storeLot: "{{ route('admin.lots.store') }}",

            deleteLot: "{{ url('admin/lots') }}",

            generateLotCode: "{{ route('admin.lots.generate.code') }}",

            getProjects: "{{ url('admin/companies') }}",

            getBlocks: "{{ url('admin/projects') }}",


        }
    </script>

    @vite(['resources/js/pages/lot.js'])
@endpush
