<!-- ===================================================== -->
<!-- MODAL CRONOGRAMA FINANCIERO MOBILE PRO -->
<!-- ===================================================== -->

<div class="modal fade" id="paymentScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">

    <div class="modal-dialog modal-fullscreen-custom modal-dialog-scrollable modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg overflow-hidden">

            <!-- ============================================= -->
            <!-- HEADER -->
            <!-- ============================================= -->

            <div class="modal-header border-0 px-3 px-md-4 py-3 schedule-header">

                <div class="d-flex align-items-center">

                    <div class="schedule-icon mr-3">

                        <i class="fas fa-calendar-alt"></i>

                    </div>

                    <div>

                        <h4 class="mb-1 font-weight-bold schedule-title">

                            Cronograma Financiero

                        </h4>

                        <small class="text-light">

                            Estado financiero actualizado de la venta

                        </small>

                    </div>

                </div>

                <button type="button" class="close text-white" data-dismiss="modal" style="opacity:1;">

                    <span>&times;</span>

                </button>

            </div>

            <!-- ============================================= -->
            <!-- BODY -->
            <!-- ============================================= -->

            <div class="modal-body bg-light p-2 p-md-4">

                <!-- ========================================= -->
                <!-- CARDS -->
                <!-- ========================================= -->

                <div class="row">

                    <div class="col-6 col-md-4 col-lg-3 mb-2 mb-md-3">

                        <div class="summary-card">

                            <small>Código</small>

                            <h5 id="ps_sale_code">—</h5>

                        </div>

                    </div>

                    <div class="col-6 col-md-4 col-lg-3 mb-2 mb-md-3">

                        <div class="summary-card">

                            <small>Lote</small>

                            <h5 id="ps_lot">—</h5>

                        </div>

                    </div>

                    <div class="col-12 col-md-4 col-lg-3 mb-2 mb-md-3">

                        <div class="summary-card">

                            <small>Cliente</small>

                            <h6 id="ps_customer">—</h6>

                        </div>

                    </div>

                    <div class="col-6 col-md-6 col-lg-1 mb-2 mb-md-3">

                        <div class="summary-card summary-info">

                            <small>Financiado</small>

                            <h5 id="ps_financed">

                                S/ 0.00

                            </h5>

                        </div>

                    </div>

                    <div class="col-6 col-md-6 col-lg-2 mb-2 mb-md-3">

                        <div class="summary-card summary-success">

                            <small>Pagado</small>

                            <h5 id="ps_paid">

                                S/ 0.00

                            </h5>

                        </div>

                    </div>

                </div>

                <!-- ========================================= -->
                <!-- RESUMEN -->
                <!-- ========================================= -->

                <div class="card border-0 shadow-sm rounded-xl mb-3">

                    <div class="card-header bg-white border-0 py-3">

                        <h6 class="mb-0 font-weight-bold">

                            <i class="fas fa-wallet text-success mr-2"></i>
                            Resumen Financiero

                        </h6>

                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-sm table-bordered mb-0">

                                <tbody>

                                    <tr>

                                        <th>Precio Original</th>

                                        <td id="rf_original">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>Total Amortizado</th>

                                        <td id="rf_amortizado">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>Total Mora</th>

                                        <td id="rf_mora">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>Descuentos</th>

                                        <td id="rf_descuentos">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr class="table-info">

                                        <th>Total Real</th>

                                        <td id="rf_real">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr class="table-success">

                                        <th>Total Pagado</th>

                                        <td id="rf_pagado">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                    <tr class="table-danger">

                                        <th>Deuda Actual</th>

                                        <td id="rf_deuda">

                                            S/ 0.00

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <!-- ========================================= -->
                <!-- AVANCE -->
                <!-- ========================================= -->

                <div class="card border-0 shadow-sm rounded-xl mb-3">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">

                            <h6 class="mb-2 mb-md-0 font-weight-bold">

                                <i class="fas fa-chart-line text-primary mr-1"></i>
                                Avance Financiero

                            </h6>

                            <span class="badge badge-primary px-3 py-2" id="ps_progress_text">

                                0%

                            </span>

                        </div>

                        <div class="progress rounded-pill" style="height:12px;">

                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                id="ps_progress_bar" style="width:0%;">

                            </div>

                        </div>

                    </div>

                </div>

                <!-- ========================================= -->
                <!-- CRONOGRAMA -->
                <!-- ========================================= -->

                <div class="card border-0 shadow-sm rounded-xl overflow-hidden mb-3">

                    <div class="card-header bg-white border-0 py-3">

                        <h6 class="mb-0 font-weight-bold">

                            <i class="fas fa-list-ul text-success mr-2"></i>
                            Cronograma de Cuotas

                        </h6>

                    </div>

                    <div class="table-mobile-wrapper">

                        <div class="table-responsive">

                            <table class="table table-hover text-center mb-0" id="tablePaymentSchedule">

                                <thead class="schedule-thead">

                                    <tr>

                                        <th>#</th>

                                        <th>Cuota</th>

                                        <th>Vencimiento</th>

                                        <th>Capital</th>

                                        <th>Interés</th>

                                        <th>Mora</th>

                                        <th>Total</th>

                                        <th>Saldo</th>

                                        <th>Estado</th>

                                    </tr>

                                </thead>

                                <tbody id="paymentScheduleBody">

                                    <tr>

                                        <td colspan="9" class="py-5 text-muted">

                                            No hay cuotas generadas

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <!-- ========================================= -->
                <!-- HISTORIAL -->
                <!-- ========================================= -->

                <div class="card border-0 shadow-sm rounded-xl overflow-hidden">

                    <div class="card-header bg-white border-0 py-3">

                        <h6 class="mb-0 font-weight-bold">

                            <i class="fas fa-history text-primary mr-2"></i>
                            Historial Financiero

                        </h6>

                    </div>

                    <div class="table-mobile-wrapper">

                        <div class="table-responsive">

                            <table class="table table-hover text-center mb-0">

                                <thead class="schedule-thead">

                                    <tr>

                                        <th>Fecha</th>

                                        <th>Operación</th>

                                        <th>Monto</th>

                                        <th>Resultado</th>

                                    </tr>

                                </thead>

                                <tbody id="financialHistoryBody">

                                    <tr>

                                        <td colspan="4" class="py-4 text-muted">

                                            No hay movimientos financieros

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- FOOTER -->
            <!-- ============================================= -->

            <div class="modal-footer bg-white border-0 p-3">

                <div class="w-100 d-flex flex-column flex-md-row justify-content-end">

                    <button type="button" class="btn btn-danger rounded-pill px-4 mb-2 mb-md-0 mr-md-2"
                        id="btnPdfSchedule">

                        <i class="fas fa-file-pdf mr-1"></i>
                        PDF

                    </button>

                    <button type="button" class="btn btn-primary rounded-pill px-4 mb-2 mb-md-0 mr-md-2"
                        id="btnPrintSchedule">

                        <i class="fas fa-print mr-1"></i>
                        Imprimir

                    </button>

                    <button type="button" class="btn btn-light border rounded-pill px-4" data-dismiss="modal">

                        <i class="fas fa-times mr-1"></i>
                        Cerrar

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- ESTILOS RESPONSIVE -->
<!-- ===================================================== -->

<style>
    /* ========================================= */
    /* MODAL */
    /* ========================================= */

    .modal-fullscreen-custom {

        max-width: 96%;

        width: 96%;

        margin: 10px auto;

    }

    .schedule-header {

        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);

        color: white;

    }

    .rounded-xl {

        border-radius: 18px;

    }

    /* ========================================= */
    /* ICON */
    /* ========================================= */

    .schedule-icon {

        width: 55px;
        height: 55px;

        min-width: 55px;

        border-radius: 16px;

        background: rgba(255, 255, 255, .15);

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 22px;

    }

    /* ========================================= */
    /* SUMMARY */
    /* ========================================= */

    .summary-card {

        background: white;

        border-radius: 16px;

        padding: 15px;

        border: 1px solid #edf2f7;

        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);

        height: 100%;

    }

    .summary-card small {

        display: block;

        color: #94a3b8;

        margin-bottom: 5px;

        font-size: 10px;

        font-weight: 700;

        text-transform: uppercase;

    }

    .summary-card h4,
    .summary-card h5,
    .summary-card h6 {

        margin: 0;

        font-weight: 700;

        color: #0f172a;

        line-height: 1.3;

        word-break: break-word;

    }

    .summary-info h5 {

        color: #0284c7;

    }

    .summary-success h5 {

        color: #16a34a;

    }

    /* ========================================= */
    /* TABLE */
    /* ========================================= */

    .table-mobile-wrapper {

        width: 100%;

        overflow-x: auto;

    }

    .schedule-thead {

        background: #f1f5f9;

    }

    .schedule-thead th {

        border: none !important;

        padding: 12px;

        font-size: 11px;

        font-weight: 700;

        color: #334155;

        white-space: nowrap;

    }

    table tbody td {

        vertical-align: middle !important;

        padding: 12px !important;

        font-size: 12px;

        white-space: nowrap;

    }

    /* ========================================= */
    /* BADGES */
    /* ========================================= */

    .badge {

        border-radius: 50px;

        font-size: 11px;

        font-weight: 700;

    }

    /* ========================================= */
    /* MOBILE */
    /* ========================================= */

    @media (max-width: 768px) {

        .modal-fullscreen-custom {

            width: 100%;

            max-width: 100%;

            margin: 0;

            height: 100vh;

        }

        .modal-content {

            min-height: 100vh;

            border-radius: 0 !important;

        }

        .modal-body {

            padding: 12px !important;

        }

        .schedule-title {

            font-size: 18px;

        }

        .schedule-icon {

            width: 45px;
            height: 45px;

            min-width: 45px;

            font-size: 18px;

            border-radius: 14px;

        }

        .summary-card {

            padding: 12px;

            border-radius: 14px;

        }

        .summary-card h4,
        .summary-card h5 {

            font-size: 18px;

        }

        .summary-card h6 {

            font-size: 14px;

        }

        .schedule-thead th {

            font-size: 10px;

            padding: 10px;

        }

        table tbody td {

            font-size: 11px;

            padding: 10px !important;

        }

        .card-header h6 {

            font-size: 14px;

        }

        .modal-footer .btn {

            width: 100%;

        }

    }

    @media (max-width: 576px) {

        .summary-card {

            min-height: 90px;

        }

        .summary-card h4,
        .summary-card h5 {

            font-size: 16px;

        }

        .summary-card h6 {

            font-size: 13px;

        }

        .table {

            min-width: 850px;

        }

    }
</style>
