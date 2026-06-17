@extends('layouts.app')

@section('subtitle', 'Dashboard')

@section('header')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h1 class="font-weight-bold text-dark mb-1">

                    <i class="fas fa-chart-line text-primary"></i>
                    Dashboard General

                </h1>

                <small class="text-muted">
                    Resumen comercial y financiero del sistema.
                </small>

            </div>

        </div>

    </div>

@stop

@section('content_body')

    <div class="container-fluid">

        {{-- KPI --}}
        <div class="row">

            <div class="col-lg-3 col-md-6 mb-3">

                <div class="small-box bg-primary shadow">

                    <div class="inner">

                        <h3 id="totalSales">0</h3>

                        <p>Total Ventas</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-file-contract"></i>
                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6 mb-3">

                <div class="small-box bg-success shadow">

                    <div class="inner">

                        <h3 id="totalCollected">S/ 0.00</h3>

                        <p>Total Recaudado</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6 mb-3">

                <div class="small-box bg-warning shadow">

                    <div class="inner">

                        <h3 id="availableLots">0</h3>

                        <p>Lotes Disponibles</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-map"></i>
                    </div>

                </div>

            </div>

            <div class="col-lg-3 col-md-6 mb-3">

                <div class="small-box bg-danger shadow">

                    <div class="inner">

                        <h3 id="rescindedSales">0</h3>

                        <p>Ventas Rescindidas</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-file-signature"></i>
                    </div>

                </div>

            </div>

        </div>

        {{-- GRAFICOS --}}
        <div class="row">

            <div class="col-lg-8">

                <div class="card shadow">

                    <div class="card-header">

                        <h5 class="mb-0">

                            <i class="fas fa-chart-bar text-primary"></i>
                            Ventas por Mes

                        </h5>

                    </div>

                    <div class="card-body">

                        <canvas id="salesChart" height="110"></canvas>

                    </div>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="card shadow">

                    <div class="card-header">

                        <h5 class="mb-0">

                            <i class="fas fa-chart-pie text-success"></i>
                            Estado de Lotes

                        </h5>

                    </div>

                    <div class="card-body">

                        <canvas id="lotsChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

        {{-- SEGUNDA FILA --}}
        <div class="row mt-3">

            <div class="col-lg-6">

                <div class="card shadow">

                    <div class="card-header">

                        <h5 class="mb-0">

                            <i class="fas fa-money-check-alt text-success"></i>
                            Cobros Mensuales

                        </h5>

                    </div>

                    <div class="card-body">

                        <canvas id="paymentsChart"></canvas>

                    </div>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="card shadow">

                    <div class="card-header">

                        <h5 class="mb-0">

                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            Alertas

                        </h5>

                    </div>

                    <div class="card-body">

                        <ul class="list-group">

                            <li class="list-group-item d-flex justify-content-between">

                                Contratos candidatos a rescisión

                                <span class="badge badge-danger">
                                    0
                                </span>

                            </li>

                            <li class="list-group-item d-flex justify-content-between">

                                Cuotas vencidas

                                <span class="badge badge-warning">
                                    0
                                </span>

                            </li>

                            <li class="list-group-item d-flex justify-content-between">

                                Lotes disponibles

                                <span class="badge badge-success">
                                    0
                                </span>

                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </div>

@stop

@push('css')
    <style>
        .small-box {
            border-radius: 15px;
        }

        .card {
            border: none;
            border-radius: 15px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        new Chart(document.getElementById('salesChart'), {

            type: 'bar',

            data: {

                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],

                datasets: [{
                    label: 'Ventas',
                    data: [5, 8, 6, 10, 12, 15]
                }]
            }
        });

        new Chart(document.getElementById('lotsChart'), {

            type: 'doughnut',

            data: {

                labels: [
                    'Disponibles',
                    'Vendidos',
                    'Reservados'
                ],

                datasets: [{
                    data: [30, 50, 20]
                }]
            }
        });

        new Chart(document.getElementById('paymentsChart'), {

            type: 'line',

            data: {

                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],

                datasets: [{
                    label: 'Cobros',
                    data: [2500, 3800, 4200, 5500, 6000, 8000],
                    fill: false
                }]
            }
        });
    </script>
@endpush
