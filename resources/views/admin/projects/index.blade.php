@extends('layouts.app')

@section('subtitle', 'Projects')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

            <div>
                <h1 class="mb-1 font-weight-bold text-dark">
                    <i class="fas fa-map-marked-alt text-primary"></i>
                    Proyectos
                </h1>

                <small class="text-muted">
                    Gestión y administración de proyectos inmobiliarios.
                </small>
            </div>

            <div>
                <button class="btn btn-primary shadow-sm px-4" type="button" data-toggle="modal" data-target="#projectModal">

                    <i class="fas fa-plus-circle mr-1"></i>
                    Nuevo Proyecto
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
                            Proyectos
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
                        Lista de Proyectos
                    </h5>

                    <small class="text-muted">
                        Proyectos registrados en el sistema
                    </small>

                </div>

            </div>

        </div>

        <div class="card-body pt-2">

            <div class="table-responsive">

                <table id="tableProject" class="table table-hover align-middle text-center w-100">

                    <thead class="bg-light">

                        <tr>

                            <th>#</th>

                            <th>ID</th>

                            <th>EMPRESA</th>

                            <th>NOMBRE</th>

                            <th>CÓDIGO</th>

                            <th>DISTRITO</th>

                            <th>PROVINCIA</th>

                            <th>DEPARTAMENTO</th>

                            <th>FECHA INICIO</th>

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
    @include('admin.projects.partials.modal')

    @include('admin.projects.partials.viewModal')

@stop

@push('css')
    <style>
        .rounded-xl {
            border-radius: 18px;
        }

        #tableProject thead th {
            border: none !important;
            font-size: 13px;
            font-weight: 700;
            color: #555;
            padding: 15px;
            white-space: nowrap;
        }

        #tableProject tbody td {
            vertical-align: middle !important;
            padding: 14px;
            border-top: 1px solid #f1f1f1;
            font-size: 14px;
        }

        #tableProject tbody tr:hover {
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

            projectList: "{{ route('admin.projects.list') }}",

            storeProject: "{{ route('admin.projects.store') }}",

            deleteProject: "{{ url('admin/projects') }}",
            
            generateProjectCode: "{{ route('admin.projects.generate.code') }}",

        }
    </script>

    @vite(['resources/js/pages/project.js'])
@endpush
