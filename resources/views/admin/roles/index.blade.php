@extends('layouts.app')
@section('subtitle', 'Roles')

@section('header')
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-7">
                <div class="d-flex align-items-center flex-wrap">
                    <div class="role-page-icon mr-3"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <h1 class="m-0 role-page-title">Roles y permisos</h1>
                        <small class="text-muted">Administra perfiles de acceso y define qué puede hacer cada tipo de usuario.</small>
                    </div>
                    @can('admin.roles.store')
                        <button class="btn btn-primary ml-sm-3 mt-2 mt-sm-0" type="button" data-toggle="modal" data-target="#roleModal">
                            <i class="fas fa-plus mr-1"></i> Nuevo rol
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-sm-5">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb float-sm-right mb-0 bg-transparent">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Roles</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@stop

@section('content_body')
    <div class="card role-list-card">
        <div class="card-header border-0 pb-0">
            <div>
                <h3 class="card-title font-weight-bold"><i class="fas fa-list-alt mr-2 text-primary"></i>Roles registrados</h3>
                <p class="text-muted small mb-0 mt-1">Edita cada rol para revisar sus permisos organizados por módulo.</p>
            </div>
        </div>

        <div class="card-body pt-3">
            <div class="table-responsive">
                <table id="tableRole" class="table table-hover table-sm text-center role-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Id</th>
                            <th>Rol</th>
                            <th>Guard Name</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('admin.roles.partials.modal')
@stop

@push('css')
<style>
    .role-page-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eaf2ff;
        color: #1f5fbf;
        font-size: 1.2rem;
        flex: 0 0 46px;
    }

    .role-page-title {
        font-size: 1.55rem;
        font-weight: 700;
        color: #1f2937;
    }

    .role-list-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .role-table thead th {
        border-top: 0;
        border-bottom: 1px solid #dfe7f1;
        background: #f7f9fc;
        color: #475569;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .role-table tbody td {
        vertical-align: middle;
        border-color: #edf1f5;
    }

    .role-action-group .btn {
        width: 34px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px !important;
        margin: 0 2px;
    }

    #roleModal .modal-dialog {
        max-width: 1080px;
    }

    #roleModal .role-modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        max-height: calc(100vh - 1.5rem);
        box-shadow: 0 24px 70px rgba(15, 23, 42, .25);
    }

    /* El formulario intermedio debe participar del layout flex del modal.
       Sin esto, Bootstrap no puede dar scroll real al modal-body. */
    #roleModal .role-modal-content > form {
        display: flex;
        flex: 1 1 auto;
        min-height: 0;
        flex-direction: column;
        overflow: hidden;
    }

    #roleModal .role-modal-header {
        border: 0;
        padding: 18px 22px;
        color: #fff;
        background: linear-gradient(135deg, #102f52 0%, #15578a 100%);
    }

    #roleModal .role-modal-header .modal-title {
        font-weight: 700;
        font-size: 1.08rem;
    }

    #roleModal .role-modal-header small {
        color: rgba(255, 255, 255, .72);
    }

    #roleModal .role-modal-header .close {
        opacity: .9;
        text-shadow: none;
        outline: none;
    }

    #roleModal .role-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
        padding: 18px 20px 20px;
        background: #f4f7fb;
        scrollbar-width: thin;
        scrollbar-color: #b8c5d3 transparent;
    }

    #roleModal .role-modal-body::-webkit-scrollbar {
        width: 9px;
    }

    #roleModal .role-modal-body::-webkit-scrollbar-track {
        background: transparent;
    }

    #roleModal .role-modal-body::-webkit-scrollbar-thumb {
        border: 2px solid transparent;
        border-radius: 999px;
        background: #b8c5d3;
        background-clip: padding-box;
    }

    #roleModal .role-modal-body::-webkit-scrollbar-thumb:hover {
        background: #8fa1b5;
        background-clip: padding-box;
    }

    #roleModal .role-name-panel,
    #roleModal .role-permissions-toolbar,
    #roleModal .role-permission-filter {
        background: #fff;
        border: 1px solid #e5ebf2;
        border-radius: 14px;
    }

    #roleModal .role-name-panel {
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    #roleModal .role-field-label {
        color: #334155;
        font-weight: 700;
        font-size: .86rem;
        margin-bottom: 7px;
    }

    #roleModal .role-name-input {
        height: 42px;
        border-radius: 10px;
        border-color: #d8e2ee;
        box-shadow: none;
    }

    #roleModal .role-name-input:focus,
    #roleModal .role-permission-filter .form-control:focus {
        border-color: #6aa8e8;
        box-shadow: 0 0 0 .18rem rgba(44, 123, 204, .10);
    }

    #roleModal .role-permissions-toolbar {
        padding: 13px 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    #roleModal .role-permissions-heading,
    #roleModal .role-permissions-summary,
    #roleModal .permission-module-title,
    #roleModal .permission-copy {
        display: flex;
        align-items: center;
    }

    #roleModal .role-permissions-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        background: #eaf2ff;
        color: #1f68b5;
    }

    #roleModal .role-permissions-heading h6 {
        font-weight: 700;
        color: #1f2937;
    }

    #roleModal .role-permissions-heading small {
        color: #7a8796;
    }

    #roleModal .permission-selected-counter {
        color: #607087;
        font-size: .78rem;
        white-space: nowrap;
    }

    #roleModal .permission-selected-counter strong {
        color: #15578a;
    }

    #roleModal .role-permission-filter {
        position: relative;
        margin-bottom: 12px;
    }

    #roleModal .role-permission-filter > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #8a98a9;
        z-index: 2;
    }

    #roleModal .role-permission-filter .form-control {
        border: 0;
        height: 42px;
        padding-left: 40px;
        border-radius: 14px;
        box-shadow: none;
    }

    #roleModal .role-permissions-grid {
        margin-left: -6px;
        margin-right: -6px;
    }

    #roleModal .permission-module-column {
        padding-left: 6px;
        padding-right: 6px;
        margin-bottom: 12px;
    }

    #roleModal .permission-module-card {
        height: 100%;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .035);
    }

    #roleModal .permission-module-header {
        min-height: 62px;
        padding: 11px 13px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #fbfcfe;
        border-bottom: 1px solid #edf1f5;
    }

    #roleModal .permission-module-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        margin-right: 9px;
        border-radius: 10px;
        color: #235e9c;
        background: #eaf2fb;
    }

    #roleModal .permission-module-title h6 {
        margin: 0;
        color: #26374a;
        font-weight: 700;
        font-size: .88rem;
    }

    #roleModal .permission-module-title small {
        color: #93a0ad;
        font-size: .68rem;
    }

    #roleModal .permission-module-toggle-wrap {
        font-size: .72rem;
        white-space: nowrap;
    }

    #roleModal .permission-module-body {
        padding: 4px 12px;
    }

    #roleModal .permission-row {
        min-height: 46px;
        padding: 8px 2px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #f0f3f6;
    }

    #roleModal .permission-row:last-child {
        border-bottom: 0;
    }

    #roleModal .permission-copy {
        min-width: 0;
    }

    #roleModal .permission-row-icon {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 26px;
        margin-right: 8px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #7a8b9c;
        font-size: .68rem;
    }

    #roleModal .permission-label {
        color: #415268;
        font-size: .78rem;
        font-weight: 600;
        line-height: 1.25;
        cursor: pointer;
    }

    #roleModal .permission-switch-label {
        min-width: 34px;
        cursor: pointer;
    }

    #roleModal .custom-control-input:checked ~ .custom-control-label::before {
        border-color: #2574c4;
        background-color: #2574c4;
    }

    #roleModal .custom-control-input:indeterminate ~ .custom-control-label::before {
        border-color: #8ca7c3;
        background-color: #8ca7c3;
    }

    #roleModal .permission-search-empty {
        padding: 28px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #8492a3;
        border: 1px dashed #ccd6e2;
        border-radius: 14px;
        background: #fff;
        text-align: center;
    }

    #roleModal .permission-search-empty i {
        font-size: 1.4rem;
    }

    #roleModal .role-modal-footer {
        padding: 12px 20px;
        border-top: 1px solid #e8edf3;
        background: #fff;
    }

    #roleModal .role-modal-footer .btn {
        min-width: 118px;
        border-radius: 9px;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        #roleModal .modal-dialog {
            margin: .5rem;
        }

        #roleModal .role-permissions-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        #roleModal .role-permissions-summary {
            width: 100%;
            justify-content: space-between;
        }

        .role-page-title {
            font-size: 1.3rem;
        }
    }
</style>
@endpush

@push('js')
<script>
    window.routes = {
        storeRole: "{{ route('admin.roles.store') }}",
        rolesList: "{{ route('admin.roles.list') }}"
    };
</script>
@vite(['resources/js/pages/roles.js'])
@endpush
