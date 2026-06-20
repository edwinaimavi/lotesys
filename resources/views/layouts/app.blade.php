@extends('adminlte::page')

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle')
        | @yield('subtitle')
    @endif
@stop

@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $user = Auth::user();

    $rutaFoto =
        $user && $user->photo
            ? Storage::url($user->photo)
            : 'https://www.shutterstock.com/image-vector/default-avatar-profile-icon-social-600nw-1906669723.jpg';
@endphp

@section('content_top_nav_right')
    <li class="nav-item dropdown user-menu-pro">
        <a class="nav-link d-flex align-items-center user-menu-trigger" data-toggle="dropdown" href="#" role="button">
            <div class="user-top-info d-none d-md-block text-right mr-2">
                <span class="user-top-name">
                    {{ $user->name ?? 'Usuario' }}
                </span>

                <small class="user-top-role d-flex align-items-center justify-content-end">
                    <span class="user-status-dot"></span>
                    Sesión activa
                </small>
            </div>

            <img src="{{ $rutaFoto }}" alt="Avatar" class="img-avatar-navbar">
        </a>

        <div class="dropdown-menu dropdown-menu-right dropdown-user-pro">
            <div class="dropdown-user-header">
                <img src="{{ $rutaFoto }}" alt="Avatar" class="dropdown-user-img">

                <div>
                    <div class="dropdown-user-name">
                        {{ $user->name ?? 'Usuario' }}
                    </div>

                    <div class="dropdown-user-email">
                        {{ $user->email ?? 'correo@empresa.com' }}
                    </div>

                    <div class="dropdown-user-session">
                        <span class="user-status-dot"></span>
                        Sesión activa
                    </div>
                </div>
            </div>

            <div class="dropdown-divider"></div>

            <a href="#" class="dropdown-item">
                <i class="fas fa-user-circle mr-2 text-primary"></i>
                Mi Perfil
            </a>

            <a href="{{ url('/home') }}" class="dropdown-item">
                <i class="fas fa-tachometer-alt mr-2 text-success"></i>
                Panel Principal
            </a>

            <div class="dropdown-divider"></div>

            <form action="{{ url('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </li>
@endsection

@section('content_header')
    <div class="content-header-pro">
        @yield('header')
    </div>
@stop

@section('content')
    <div id="divLoading">
        <div class="loader-card">
            <img src="{{ asset('images/loading.svg') }}" alt="Loading..." />
            <span>Procesando...</span>
        </div>
    </div>

    <div class="main-content-pro">
        @yield('content_body')
    </div>
@stop

@section('footer')
    <div class="footer-pro">
        <div>
            <strong>
                <a href="{{ config('app.company_url', '#') }}">
                    {{ config('app.company_name', 'CiCoSys') }}
                </a>
            </strong>

            <span class="footer-separator">•</span>

            <span>
                Sistema de gestión inmobiliaria
            </span>
        </div>

        <div>
            Versión {{ config('app.version', '1.0.0') }}
        </div>
    </div>
@stop

@push('css')
    {{-- DataTables Bootstrap 4 --}}
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/responsive.bootstrap4.css') }}">

    {{-- DataTables Buttons --}}
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" rel="stylesheet">

    <style>
        :root {
            --cico-primary: #0f4c81;
            --cico-primary-2: #0f66d0;
            --cico-dark: #07111f;
            --cico-dark-2: #0f172a;
            --cico-soft: #f4f7fb;
            --cico-border: #e5eaf0;
            --cico-muted: #64748b;
            --cico-success: #16a34a;
            --cico-warning: #f59e0b;
            --cico-danger: #dc2626;
            --cico-radius: 16px;
            --cico-shadow: 0 8px 28px rgba(15, 23, 42, .08);
        }

        body {
            background: #eef3f8 !important;
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body,
        .content-wrapper {
            color: #1f2937;
        }

        .content-wrapper {
            background:
                radial-gradient(circle at top left, rgba(15, 102, 208, .08), transparent 28%),
                linear-gradient(180deg, #f6f9fc 0%, #eef3f8 100%) !important;
        }

        .content {
            padding-bottom: 1.5rem;
        }

        .content-header {
            padding: 18px 1rem 8px;
        }

        .content-header-pro {
            width: 100%;
        }

        .main-content-pro {
            animation: fadeInPage .22s ease;
        }

        @keyframes fadeInPage {
            from {
                opacity: .4;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /*
                |--------------------------------------------------------------------------
                | NAVBAR
                |--------------------------------------------------------------------------
                */

        .main-header {
            min-height: 62px;
            border-bottom: 0 !important;
            background: rgba(255, 255, 255, .92) !important;
            backdrop-filter: blur(16px);
            box-shadow: 0 4px 24px rgba(15, 23, 42, .06) !important;
        }

        .main-header .nav-link {
            color: #334155 !important;
            border-radius: 12px;
            transition: all .18s ease;
        }

        .main-header .nav-link:hover {
            background: #eef6ff;
            color: var(--cico-primary-2) !important;
        }

        .navbar-search-block {
            background: rgba(255, 255, 255, .96) !important;
            backdrop-filter: blur(12px);
        }

        /*
                |--------------------------------------------------------------------------
                | SIDEBAR / BRAND
                |--------------------------------------------------------------------------
                */

        .main-sidebar {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, .12), transparent 32%),
                linear-gradient(180deg, #07111f 0%, #0f172a 48%, #0b2f4f 100%) !important;
            box-shadow: 8px 0 30px rgba(15, 23, 42, .22) !important;
        }

        .brand-link {
            height: 64px;
            display: flex !important;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, .08) !important;
            background: rgba(255, 255, 255, .03) !important;
        }

        .brand-link .brand-image {
            float: none !important;
            max-height: 42px !important;
            width: auto !important;
            margin-left: .45rem !important;
            margin-right: .65rem !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            opacity: 1 !important;
        }

        .brand-link .brand-text {
            color: #ffffff !important;
            font-weight: 800 !important;
            letter-spacing: .2px;
        }

        .sidebar {
            padding: 12px 10px 18px;
        }

        .nav-sidebar>.nav-item {
            margin-bottom: 3px;
        }

        .nav-sidebar .nav-link {
            border-radius: 13px !important;
            color: rgba(255, 255, 255, .78) !important;
            font-weight: 600;
            padding: .72rem .85rem;
            transition: all .18s ease;
        }

        .nav-sidebar .nav-link:hover {
            background: rgba(255, 255, 255, .08) !important;
            color: #ffffff !important;
            transform: translateX(2px);
        }

        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, #0f66d0, #38bdf8) !important;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(15, 102, 208, .28);
        }

        .nav-sidebar .nav-icon {
            font-size: .95rem !important;
            margin-right: .45rem !important;
        }

        .nav-sidebar .nav-treeview {
            padding-left: 6px;
            margin-top: 3px;
        }

        .nav-treeview .nav-link {
            font-size: .88rem;
            padding-top: .58rem;
            padding-bottom: .58rem;
            background: transparent !important;
        }

        .nav-treeview .nav-link.active {
            background: rgba(15, 102, 208, .20) !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        .nav-header {
            color: rgba(255, 255, 255, .40) !important;
            font-size: .68rem !important;
            letter-spacing: .08em;
            font-weight: 900;
            padding: 1rem .75rem .45rem !important;
        }

        .sidebar-search-results .list-group-item {
            background: #ffffff !important;
            color: #111827 !important;
        }

        /*
                |--------------------------------------------------------------------------
                | AVATAR / USER MENU
                |--------------------------------------------------------------------------
                */

        .img-avatar-navbar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .16);
            background: #fff;
        }

        .user-top-info {
            line-height: 1.1;
        }

        .user-top-name {
            display: block;
            font-size: .82rem;
            font-weight: 800;
            color: #334155;
        }

        .user-top-role {
            display: block;
            color: #64748b;
            font-size: .69rem;
            font-weight: 700;
        }

        .dropdown-user-pro {
            width: 285px;
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(15, 23, 42, .18);
            padding: .5rem;
        }

        .dropdown-user-pro .dropdown-item {
            border-radius: 12px;
            font-size: .88rem;
            padding: .68rem .8rem;
            font-weight: 600;
        }

        .dropdown-user-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: .8rem;
            border-radius: 15px;
            background: linear-gradient(135deg, #eef6ff, #f8fafc);
        }

        .dropdown-user-img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
        }

        .dropdown-user-name {
            font-size: .92rem;
            font-weight: 900;
            color: #0f172a;
        }

        .dropdown-user-email {
            font-size: .75rem;
            color: #64748b;
            word-break: break-all;
        }

        /*
                |--------------------------------------------------------------------------
                | LOADER
                |--------------------------------------------------------------------------
                */

        #divLoading {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            background: rgba(248, 250, 252, .72);
            backdrop-filter: blur(5px);
            z-index: 99999;
        }

        .loader-card {
            width: 150px;
            min-height: 128px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 24px 70px rgba(15, 23, 42, .18);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: 1px solid rgba(226, 232, 240, .9);
        }

        #divLoading img {
            width: 58px;
            height: 58px;
        }

        .loader-card span {
            font-size: .78rem;
            font-weight: 800;
            color: #475569;
        }

        /*
                |--------------------------------------------------------------------------
                | CARDS
                |--------------------------------------------------------------------------
                */

        .card {
            border: 0 !important;
            border-radius: var(--cico-radius) !important;
            background: #ffffff !important;
            box-shadow: var(--cico-shadow) !important;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            border-bottom: 1px solid var(--cico-border) !important;
            padding: .9rem 1rem;
        }

        .card-header h1,
        .card-header h2,
        .card-header h3,
        .card-title {
            color: #0f172a !important;
            font-weight: 900 !important;
            letter-spacing: -.2px;
        }

        .card-body {
            background: #ffffff;
        }

        .small-box {
            border-radius: 18px !important;
            box-shadow: var(--cico-shadow) !important;
            overflow: hidden;
        }

        .small-box .icon {
            opacity: .18;
        }

        /*
                |--------------------------------------------------------------------------
                | BOTONES
                |--------------------------------------------------------------------------
                */

        .btn {
            border-radius: 11px;
            font-weight: 700;
            transition: all .16s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0f66d0, #38bdf8) !important;
            border: 0 !important;
            box-shadow: 0 10px 22px rgba(15, 102, 208, .22);
        }

        .btn-success {
            background: linear-gradient(135deg, #16a34a, #22c55e) !important;
            border: 0 !important;
            box-shadow: 0 10px 22px rgba(22, 163, 74, .18);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #ef4444) !important;
            border: 0 !important;
            box-shadow: 0 10px 22px rgba(220, 38, 38, .18);
        }

        .btn-warning {
            border: 0 !important;
            box-shadow: 0 10px 22px rgba(245, 158, 11, .18);
        }

        .btn-light,
        .btn-outline-secondary {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
        }

        .btn-action {
            border: none;
            background: transparent;
            color: #64748b;
            transition: all .16s ease;
            border-radius: 10px;
        }

        .btn-action:hover {
            color: var(--cico-primary-2);
            background: #eef6ff;
            transform: scale(1.08);
        }

        /*
                |--------------------------------------------------------------------------
                | FORMULARIOS
                |--------------------------------------------------------------------------
                */

        label,
        .form-label {
            font-size: .78rem;
            font-weight: 800;
            color: #475569;
            letter-spacing: .03em;
        }

        .form-control,
        .form-select,
        select.form-control {
            border-radius: 11px !important;
            border: 1px solid #dbe3ef !important;
            background: #fbfdff !important;
            color: #0f172a;
            transition: all .16s ease;
        }

        .form-control:focus,
        .form-select:focus,
        select.form-control:focus {
            border-color: #0f66d0 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 .18rem rgba(15, 102, 208, .10) !important;
        }

        .form-control.form-control-sm {
            min-height: 35px;
            padding: 7px 10px;
        }

        .form-control-lg {
            padding: 10px 14px;
            height: auto;
        }

        .input-group-text {
            border-color: #dbe3ef !important;
            background: #f8fafc !important;
            border-radius: 11px;
        }

        /*
                |--------------------------------------------------------------------------
                | TABLAS / DATATABLES
                |--------------------------------------------------------------------------
                */

        .table-responsive {
            overflow-x: auto !important;
        }

        table.dataTable,
        .tableStiles {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            font-size: .88rem;
        }

        table.dataTable thead th,
        .tableStiles thead tr,
        .tableStiles thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%) !important;
            color: #334155 !important;
            font-weight: 900 !important;
            text-transform: uppercase;
            letter-spacing: .045em;
            font-size: .74rem;
            border-bottom: 1px solid #dbe3ef !important;
        }

        table.dataTable tbody td,
        .tableStiles tbody td {
            vertical-align: middle;
            border-color: #edf2f7 !important;
        }

        table.dataTable tbody tr:hover,
        .tableStiles tbody tr:hover {
            background: #f8fbff !important;
        }

        div.dataTables_wrapper {
            width: 100%;
        }

        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
        }

        div.dataTables_wrapper div.dataTables_filter input {
            border-radius: 12px !important;
            border: 1px solid #dbe3ef !important;
            padding: 6px 11px;
            margin-left: 8px;
        }

        div.dataTables_wrapper div.dataTables_length select {
            border-radius: 10px !important;
            border: 1px solid #dbe3ef !important;
            padding: 4px 8px;
        }

        div.dataTables_wrapper div.dt-buttons {
            margin-top: 12px;
            display: flex;
            justify-content: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        div.dataTables_wrapper div.dt-buttons .btn {
            border-radius: 10px;
            padding: 6px 14px;
            font-size: .82rem;
            flex: 0 0 auto;
        }

        .page-item .page-link {
            border-radius: 10px !important;
            margin: 0 2px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 700;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #0f66d0, #38bdf8) !important;
            border: 0 !important;
            color: #fff;
        }

        /*
                |--------------------------------------------------------------------------
                | MODALES
                |--------------------------------------------------------------------------
                */

        .modal-content {
            border: 0 !important;
            border-radius: 20px !important;
            overflow: hidden;
            box-shadow: 0 30px 90px rgba(15, 23, 42, .28) !important;
        }

        .modal-header {
            padding: 1rem 1.15rem !important;
            background: linear-gradient(135deg, #0f172a 0%, #0f4c81 100%) !important;
            border-bottom: 0 !important;
            color: #ffffff !important;
        }

        .modal-title {
            font-weight: 900 !important;
            color: #ffffff !important;
        }

        .modal-header .close {
            color: #ffffff !important;
            text-shadow: none !important;
            opacity: .85;
            transition: all .16s ease;
        }

        .modal-header .close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .modal-body {
            background: #f6f9fc !important;
        }

        .modal-footer {
            background: #ffffff;
            border-top: 1px solid #edf2f7;
        }

        .icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .15);
            color: #ffffff;
        }

        .avatar-preview img,
        #imgPreview {
            object-fit: cover;
            border: 5px solid #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
        }

        /*
                |--------------------------------------------------------------------------
                | BADGES / ALERTAS
                |--------------------------------------------------------------------------
                */

        .badge {
            border-radius: 999px;
            padding: .45rem .65rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .alert {
            border-radius: 16px;
            border: 0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        }

        /*
                |--------------------------------------------------------------------------
                | FOOTER
                |--------------------------------------------------------------------------
                */

        .main-footer {
            border-top: 0 !important;
            background: #ffffff !important;
            color: #64748b !important;
            box-shadow: 0 -5px 20px rgba(15, 23, 42, .04);
            font-size: .82rem;
        }

        .footer-pro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
        }

        .footer-pro a {
            color: var(--cico-primary-2);
            font-weight: 900;
        }

        .footer-separator {
            color: #cbd5e1;
            margin: 0 6px;
        }

        /*
                |--------------------------------------------------------------------------
                | RESPONSIVE
                |--------------------------------------------------------------------------
                */

        @media (max-width: 768px) {
            .content-header {
                padding-top: 12px;
            }

            .main-header {
                min-height: 56px;
            }

            .user-menu-pro .user-top-info {
                display: none !important;
            }

            div.dataTables_wrapper div.dataTables_filter,
            div.dataTables_wrapper div.dataTables_length {
                text-align: center !important;
                margin-bottom: 10px;
            }

            div.dataTables_wrapper div.dataTables_filter input {
                width: 100% !important;
                margin-top: 6px;
                margin-left: 0;
            }

            div.dataTables_wrapper div.dt-buttons {
                justify-content: center;
            }

            div.dataTables_wrapper div.dt-buttons .btn {
                flex: 0 1 auto;
            }

            .footer-pro {
                justify-content: center;
                text-align: center;
            }
        }

        .user-menu-trigger {
            padding: 6px 10px !important;
            border-radius: 14px;
            transition: all .18s ease;
        }

        .user-menu-trigger:hover {
            background: #f8fbff !important;
        }

        .img-avatar-navbar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            background: #fff;
        }

        .user-top-info {
            line-height: 1.1;
        }

        .user-top-name {
            display: block;
            font-size: .85rem;
            font-weight: 700;
            color: #334155;
            /* antes muy negro */
            letter-spacing: .1px;
        }

        .user-top-role {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            color: #64748b;
            /* tono más suave */
            font-size: .72rem;
            font-weight: 600;
            margin-top: 3px;
        }

        .user-status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .18);
            animation: pulseOnline 1.8s infinite;
            flex-shrink: 0;
        }

        @keyframes pulseOnline {
            0% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, .35);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }

        .dropdown-user-pro {
            width: 290px;
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(15, 23, 42, .16);
            padding: .5rem;
        }

        .dropdown-user-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: .9rem;
            border-radius: 15px;
            background: linear-gradient(135deg, #eef6ff, #f8fafc);
        }

        .dropdown-user-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        }

        .dropdown-user-name {
            font-size: .92rem;
            font-weight: 800;
            color: #334155;
            /* menos negro */
            margin-bottom: 2px;
        }

        .dropdown-user-email {
            font-size: .75rem;
            color: #64748b;
            word-break: break-all;
            margin-bottom: 6px;
        }

        .dropdown-user-session {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .74rem;
            font-weight: 600;
            color: #475569;
            background: rgba(255, 255, 255, .65);
            padding: 5px 10px;
            border-radius: 999px;
        }

        .dropdown-user-pro .dropdown-item {
            border-radius: 12px;
            font-size: .88rem;
            padding: .68rem .8rem;
            font-weight: 600;
            color: #334155;
        }

        .dropdown-user-pro .dropdown-item:hover {
            background: #f8fbff;
        }
    </style>
@endpush

@push('js')
    {{-- SweetAlert2 --}}
    <script src="{{ asset('vendor/sweetalert2/js/sweetalert2@11.js') }}"></script>

    {{-- DataTables Bootstrap 4 --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

    {{-- DataTables Responsive --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    {{-- DataTables Buttons --}}
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    {{-- Capturas y PDF --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        /*
                |--------------------------------------------------------------------------
                | CONFIGURACIÓN GLOBAL AJAX
                |--------------------------------------------------------------------------
                */
        $(document).ready(function() {

            if ($('meta[name="csrf-token"]').length) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            $('[data-toggle="tooltip"]').tooltip();

            setTimeout(function() {
                $('#divLoading').fadeOut(150);
            }, 250);

        });

        /*
        |--------------------------------------------------------------------------
        | LOADER GLOBAL
        |--------------------------------------------------------------------------
        */
        window.showLoader = function() {
            $('#divLoading').css('display', 'flex');
        };

        window.hideLoader = function() {
            $('#divLoading').fadeOut(150);
        };

        /*
        |--------------------------------------------------------------------------
        | SWEETALERT HELPERS
        |--------------------------------------------------------------------------
        */
        window.toastSuccess = function(message) {
            Swal.fire({
                icon: 'success',
                title: message || 'Operación realizada correctamente.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        };

        window.toastError = function(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message || 'Ocurrió un error inesperado.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        };

        /*
        |--------------------------------------------------------------------------
        | PREVIEW IMAGEN CLIENTE
        |--------------------------------------------------------------------------
        */
        function previewClientImage(event) {
            const input = event.target;

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    $('#client_img_preview').attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        window.previewClientImage = previewClientImage;

        /*
        |--------------------------------------------------------------------------
        | GENERAR NOMBRE COMPLETO
        |--------------------------------------------------------------------------
        */
        $(document).on('click', '#btnGenerateFullName', function() {

            const fn = ($('#first_name').val() || '').trim();
            const ln = ($('#last_name').val() || '').trim();
            const full = (fn + ' ' + ln).trim();

            if (!full) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Rellena nombres o apellidos para generar el nombre completo.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2800
                });

                return;
            }

            if ($('#full_name').length) {
                $('#full_name').val(full);
            }

            const btn = $(this);

            btn.removeClass('btn-outline-secondary')
                .addClass('btn-success')
                .html('<i class="fas fa-check mr-1"></i> Generado');

            setTimeout(function() {
                btn.removeClass('btn-success')
                    .addClass('btn-outline-secondary')
                    .html('<i class="fas fa-sync-alt mr-1"></i> Generar Nombre');
            }, 1400);

        });

        /*
        |--------------------------------------------------------------------------
        | MODAL CLIENTE
        |--------------------------------------------------------------------------
        */
        $(document).on('shown.bs.modal', '#clientModal', function() {

            if ($('#document_type').length) {
                $('#document_type').focus();
            }

            if ($('#error-messages').length) {
                $('#error-messages').addClass('d-none').empty();
            }

        });

        /*
        |--------------------------------------------------------------------------
        | MEJORA RESPONSIVE DATATABLES
        |--------------------------------------------------------------------------
        */
        $(window).on('resize', function() {

            if ($.fn.DataTable) {
                $.fn.dataTable
                    .tables({
                        visible: true,
                        api: true
                    })
                    .columns.adjust()
                    .responsive.recalc();
            }

        });
    </script>
@endpush
