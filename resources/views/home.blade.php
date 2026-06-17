@extends('layouts.app')

@section('subtitle', 'Dashboard')

@section('header')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <h1 class="font-weight-bold text-dark mb-1">
                    <i class="fas fa-chart-line text-primary"></i>
                    Dashboard General
                </h1>
                <small class="text-muted">
                    Resumen comercial y financiero del sistema.
                </small>
            </div>

            <div class="text-right">
                <small class="text-muted d-block">Fecha de actualización</small>
                <strong>{{ now()->format('d/m/Y H:i') }}</strong>
            </div>
        </div>
    </div>
@stop

@section('content_body')
    <div class="container-fluid">

        {{-- KPI --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-primary shadow-sm">
                    <div class="inner">
                        <h3>{{ $totalSales ?? 0 }}</h3>
                        <p>Total Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <a href="{{ route('admin.sales.index') }}" class="small-box-footer">
                        Ver ventas <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-success shadow-sm">
                    <div class="inner">
                        <h3>S/ {{ number_format($totalCollected ?? 0, 2) }}</h3>
                        <p>Total Recaudado</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <a href="{{ route('admin.payments.index') }}" class="small-box-footer">
                        Ver pagos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-warning shadow-sm">
                    <div class="inner">
                        <h3>{{ $availableLots ?? 0 }}</h3>
                        <p>Lotes Disponibles</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <a href="{{ route('admin.lots.index') }}" class="small-box-footer">
                        Ver lotes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-danger shadow-sm">
                    <div class="inner">
                        <h3>{{ $rescindedSales ?? 0 }}</h3>
                        <p>Ventas Rescindidas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <a href="{{ route('admin.rescissions.index') }}" class="small-box-footer">
                        Ver rescisiones <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- SEGUNDA FILA --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-info shadow-sm">
                    <div class="inner">
                        <h3>{{ $activeSales ?? 0 }}</h3>
                        <p>Ventas Activas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <a href="{{ route('admin.sales.index') }}" class="small-box-footer">
                        Ver detalle <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-secondary shadow-sm">
                    <div class="inner">
                        <h3>{{ $soldLots ?? 0 }}</h3>
                        <p>Lotes Vendidos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <a href="{{ route('admin.lots.index') }}" class="small-box-footer">
                        Ver lotes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-dark shadow-sm">
                    <div class="inner">
                        <h3>{{ $availableLots ?? 0 }}</h3>
                        <p>Disponibles para Venta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <a href="{{ route('admin.lots.index') }}" class="small-box-footer">
                        Ver inventario <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="small-box bg-danger shadow-sm">
                    <div class="inner">
                        <h3>{{ $rescindedSales ?? 0 }}</h3>
                        <p>Contratos Rescindidos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="{{ route('admin.rescissions.index') }}" class="small-box-footer">
                        Ver historial <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- GRAFICOS --}}
        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card shadow-sm border-0 rounded-xl h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-2">
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-chart-bar text-primary"></i>
                            Ventas por Mes
                        </h5>
                        <small class="text-muted">Comportamiento mensual de ventas registradas.</small>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card shadow-sm border-0 rounded-xl h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-2">
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-chart-pie text-success"></i>
                            Estado de Lotes
                        </h5>
                        <small class="text-muted">Distribución del inventario actual.</small>
                    </div>
                    <div class="card-body">
                        <canvas id="lotsChart" height="240"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm border-0 rounded-xl h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-2">
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-money-check-alt text-success"></i>
                            Cobros Mensuales
                        </h5>
                        <small class="text-muted">Pagos acumulados por mes.</small>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentsChart" height="140"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm border-0 rounded-xl h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-2">
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            Alertas Rápidas
                        </h5>
                        <small class="text-muted">Estado operativo del sistema.</small>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Ventas activas
                                <span class="badge badge-info badge-pill px-3 py-2">{{ $activeSales ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Ventas rescindidas
                                <span class="badge badge-danger badge-pill px-3 py-2">{{ $rescindedSales ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Lotes disponibles
                                <span class="badge badge-success badge-pill px-3 py-2">{{ $availableLots ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Total recaudado
                                <span class="badge badge-primary badge-pill px-3 py-2">S/
                                    {{ number_format($totalCollected ?? 0, 2) }}</span>
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
            border-radius: 16px;
            position: relative;
            display: block;
            margin-bottom: 20px;
            color: #fff;
            padding: 18px 16px 12px 16px;
            overflow: hidden;
            min-height: 128px;
        }

        .small-box .inner h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.1;
        }

        .small-box .inner p {
            font-size: 1rem;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .small-box .icon {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 60px;
            opacity: .18;
        }

        .small-box-footer {
            display: block;
            margin-top: 14px;
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 600;
        }

        .small-box-footer:hover {
            color: #fff;
            text-decoration: none;
        }

        .card {
            border-radius: 16px;
        }

        .rounded-xl {
            border-radius: 16px;
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #eef0f3;
        }

        .list-group-item {
            border-left: 0;
            border-right: 0;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const salesChartData = {!! json_encode($salesByMonth ?? [5, 8, 6, 10, 12, 15]) !!};

        const paymentsChartData = {!! json_encode($paymentsByMonth ?? [2500, 3800, 4200, 5500, 6000, 8000]) !!};

        new Chart(document.getElementById('salesChart'), {
            type: 'bar',
            data: {
                labels: [
                    'Ene',
                    'Feb',
                    'Mar',
                    'Abr',
                    'May',
                    'Jun',
                    'Jul',
                    'Ago',
                    'Sep',
                    'Oct',
                    'Nov',
                    'Dic'
                ],
                datasets: [{
                    label: 'Ventas',
                    data: salesChartData,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(document.getElementById('lotsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Disponibles', 'Vendidos', 'Reservados'],
                datasets: [{
                    data: [
                        {{ $availableLots ?? 0 }},
                        {{ $soldLots ?? 0 }},
                        {{ $reservedLots ?? 0 }}
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(document.getElementById('paymentsChart'), {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Cobros',
                    data: paymentsChartData,
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
@endpush
