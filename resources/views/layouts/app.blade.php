{{-- <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
 --}}

@extends('adminlte::page')

{{-- @section('title', 'Dashboard') --}}
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

{{-- 🔽 AGREGA ESTO --}}
@section('content_top_nav_right')
    <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" role="button">
            <img src="{{ $rutaFoto }}" alt="Avatar" class="img-avatar-navbar">
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="#" class="dropdown-item">Mi Perfil</a>
            <a href="#" class="dropdown-item">Cerrar Sesión</a>
        </div>
    </li>
@endsection


@section('content_header')
    @yield('header')
@stop

@section('content')


    <div id="divLoading">
        <div>
            <img src="{{ asset('images/loading.svg') }}" alt="Loading..." />

        </div>
    </div>

    @yield('content_body')
@stop

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>

    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'Sys Ventas (3ACP)') }}
        </a>
    </strong>
@stop



{{-- @section('css')
    
@stop --}}

{{-- @section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop --}}
@push('js')
    {{--     <script src="{{ asset('vendor/datatables/js/popper.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap4.js') }}"></script>    
    <script src="{{ asset('vendor/datatables/js/dataTables.responsive.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/responsive.bootstrap4.js') }}"></script> --}}
    <script src="{{ asset('vendor/sweetalert2/js/sweetalert2@11.js') }}"></script>



    <!-- DataTables + Bootstrap 5 -->
    <!-- DataTables Bootstrap 4 -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

    <!-- Responsive -->
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    <!-- Buttons -->
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>




    {{--     <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> --}}

    <script>
        $(document).ready(function() {

        });
    </script>
    <script>
        // preview imagen de cliente
        function previewClientImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#client_img_preview').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Generar full_name desde first+last (botón)
        $('#btnGenerateFullName').on('click', function() {
            const fn = ($('#first_name').val() || '').trim();
            const ln = ($('#last_name').val() || '').trim();
            const full = (fn + ' ' + ln).trim();
            if (!full) {
                alert('Rellena nombres o apellidos para generar el nombre completo.');
                return;
            }
            // si quieres, asignar a un campo hidden full_name (si existe)
            // $('#full_name').val(full);
            $(this).removeClass('btn-outline-secondary').addClass('btn-success').text('Generado');
            setTimeout(() => $(this).removeClass('btn-success').addClass('btn-outline-secondary').html(
                '<i class="fas fa-sync-alt mr-1"></i> Generar Nombre'), 1400);
        });

        // Opcional: focus inicial cuando se abre el modal
        $('#clientModal').on('shown.bs.modal', function() {
            $('#document_type').focus();
            $('#error-messages').addClass('d-none').empty();
        });
    </script>
@endpush

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/responsive.bootstrap4.css') }}">
    {{--   <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2-bootstrap4.min.css') }}"> --}}

    <style type="text/css">
        #divLoading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            background: rgba(254, 254, 255, 0.65);
            z-index: 9999;
        }

        #divLoading img {
            width: 60px;
            height: 60px;

        }

        /* ✅ Estilo para avatar */
        .img-avatar-navbar {
            margin-top: -8px;
            margin-right: -15px;
            /* 🔸 Empuja un poco a la derecha */
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
        }

        /* INICIO ESTILOS DEL DATA TABLE */

        /* 🔹 Elimina el scroll horizontal innecesario en DataTables */
        .dataTables_wrapper {
            overflow-x: hidden !important;
        }

        /* 🔹 Asegura que la tabla se ajuste correctamente */
        table.dataTable {
            width: 100% !important;
        }

        /* 🔹 Previene desbordes por padding o márgenes */
        .table-responsive {
            overflow-x: visible !important;
        }

        /* 💎 Tarjeta general */
        .card {
            border-radius: 10px;
            border: none;
            background-color: #fff;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
        }

        /* 🔹 Encabezado */
        .card-header {
            background-color: #f1f1f1 !important;
            border-bottom: 1px solid #ddd;
        }

        /* 🔹 Título */
        .card-header h3 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #333;
        }

        /* 🔹 Botón “Nueva Sucursal” */
        .card-header .btn {
            border-radius: 8px;
            color: #444;
            border-color: #ccc;
            background-color: #f9f9f9;
            transition: all 0.2s ease;
        }

        .card-header .btn:hover {
            background-color: #e9e9e9;
            border-color: #bbb;
        }

        /* 🔹 Tabla */
        .tableStiles {
            border-collapse: separate;
            border-spacing: 0;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            font-size: 0.9rem;
        }

        /* 🔹 Encabezado de la tabla */
        .tableStiles thead tr {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* 🔹 Filas */
        .tableStiles tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .tableStiles tbody tr:hover {
            background-color: #f1f1f1;
            transition: background-color 0.2s ease;
        }

        /* 🔹 Acciones */
        .btn-action {
            border: none;
            background: transparent;
            color: #555;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            color: #000;
            transform: scale(1.15);
        }

        /*FIN DE ESTILOS DEL DATA TABLE  */


        /*INICIO DE ESTILOS DEL MODAL */
        .modal-content {
            border-radius: 15px !important;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-body {
            background-color: #f8f9fa;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #bdbdbd;
            box-shadow: 0 0 0 0.1rem rgba(0, 0, 0, 0.1);
        }

        .btn-light {
            background-color: #f9f9f9;
            border-color: #dcdcdc;
        }

        .btn-light:hover {
            background-color: #efefef;
        }

        /*   .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            } */

        /*  .btn-secondary:hover {
            background-color: #5c636a;
            }
     */
        #imgPreview {
            object-fit: cover;
            border: 2px solid #e3e3e3;
        }


        /* FIN DE ESTILOS DEL MODAL */

        /* Select con apariencia moderna y sin exceso de borde */
        .form-control-lg {
            padding: 10px 14px;
            height: auto;
            border: 1px solid #dcdcdc !important;
            transition: all 0.2s ease-in-out;
        }

        /* Sombra y borde al enfocar */
        .form-control-lg:focus {
            border-color: #a9a9a9 !important;
            box-shadow: 0 0 5px rgba(160, 160, 160, 0.3);
            background-color: #fff;
        }

        /* Label elegante */
        .form-label {
            font-size: 0.9rem;
            color: #6c757d;
            letter-spacing: 0.2px;
        }







        /* ✅ Ajustes visuales para botones al final del DataTable */
        div.dataTables_wrapper div.dt-buttons {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        div.dataTables_wrapper .dt-buttons .btn {
            border-radius: 6px;
            padding: 6px 14px;
        }

        /* ✅ Móvil: los elementos se centran verticalmente */
        @media (max-width: 768px) {
            div.dataTables_wrapper div.dataTables_filter {
                text-align: center !important;
                margin-bottom: 10px;
            }

            div.dataTables_wrapper div.dataTables_filter input {
                width: 100% !important;
                margin-top: 6px;
            }
        }


        div.dataTables_wrapper div.dt-buttons {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }


        /* ✅ Ajuste del buscador y selector */
        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
        }

        div.dataTables_wrapper div.dataTables_length {
            text-align: left;
        }

        /* ✅ En móvil: apila el buscador y el selector de registros */
        @media (max-width: 768px) {

            div.dataTables_wrapper div.dataTables_filter,
            div.dataTables_wrapper div.dataTables_length {
                text-align: center !important;
                margin-bottom: 10px;
            }

            div.dataTables_wrapper div.dataTables_filter input {
                width: 100% !important;
                margin-top: 6px;
            }
        }

        /* ✅ Botones más elegantes y compactos debajo de la tabla */
        div.dataTables_wrapper div.dt-buttons {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        div.dataTables_wrapper div.dt-buttons .btn {
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 0.85rem;
            flex: 0 0 auto;
            /* Evita que crezcan a todo el ancho */
        }

        /* ✅ En móvil: botones centrados, pero sin ocupar todo el ancho */
        @media (max-width: 768px) {
            div.dataTables_wrapper div.dt-buttons {
                justify-content: center;
            }

            div.dataTables_wrapper div.dt-buttons .btn {
                flex: 0 1 auto;
            }
        }






        /* Modal elegante (Bootstrap4) */
        .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            padding: 18px 20px;
            background: linear-gradient(90deg, #ffffff, #f6f9fb);
            border-bottom: 1px solid #e6ebef;
        }

        .modal-title {
            font-weight: 700;
            color: #2f3b43;
        }

        .icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3f6f8;
            box-shadow: 0 6px 14px rgba(47, 63, 78, 0.04);
        }

        .icon-circle .fas {
            color: #4b5560;
            font-size: 18px;
        }

        .avatar-preview img {
            border-radius: 50%;
            border: 6px solid #fff;
            box-shadow: 0 10px 30px rgba(47, 63, 78, 0.08);
        }

        /* cards y campos */
        .card {
            border-radius: 10px;
            background: transparent;
            box-shadow: none;
        }

        .form-control.form-control-sm {
            border-radius: 8px;
            border: 1px solid #e1e6ea;
            background: #fbfdff;
            padding: 7px 10px;
        }

        .form-control.form-control-sm:focus {
            box-shadow: 0 8px 20px rgba(47, 63, 78, 0.06);
            border-color: #cbd5da;
            background: #fff;
        }

        select.form-control-sm {
            height: 36px;
        }

        /* badges y left meta */
        #left_status.badge {
            font-weight: 600;
            background: #22c55e;
            color: #fff;
            border-radius: 8px;
            padding: .55rem .7rem;
        }

        /* botones */
        .btn-primary {
            background: #0d6efd;
            border-color: #0d6efd;
            box-shadow: 0 6px 18px rgba(13, 110, 253, 0.12);
        }

        .btn-light {
            background: #fff;
            border: 1px solid #e6eaee;
        }

        .btn-outline-secondary {
            border-radius: 8px;
        }

        /* responsive tweaks */
        @media (max-width: 991.98px) {
            .avatar-preview img {
                width: 110px;
                height: 110px;
            }

            .icon-circle {
                width: 42px;
                height: 42px;
            }
        }
    </style>
@endpush
