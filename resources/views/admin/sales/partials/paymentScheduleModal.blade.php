<div class="modal fade schedule-scope" id="paymentScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered schedule-dialog">
        <div class="modal-content border-0 shadow-lg schedule-modal-content">

            <div class="modal-header schedule-header border-0 px-3 px-md-4 py-3">
                <div class="d-flex align-items-center min-w-0">
                    <div class="schedule-icon mr-3"><i class="fas fa-calendar-alt"></i></div>
                    <div class="min-w-0">
                        <h4 class="mb-0 font-weight-bold schedule-title">Cronograma Financiero</h4>
                        <small class="schedule-subtitle">Estado financiero actualizado de la venta</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity:1;">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body schedule-body p-3">

                <div class="schedule-identification mb-3">
                    <div class="row no-gutters">
                        <div class="col-6 col-md-2 schedule-id-cell">
                            <small>Código</small>
                            <strong id="ps_sale_code">—</strong>
                        </div>
                        <div class="col-6 col-md-2 schedule-id-cell">
                            <small>Empresa</small>
                            <strong id="ps_company">—</strong>
                        </div>
                        <div class="col-6 col-md-2 schedule-id-cell">
                            <small>Proyecto</small>
                            <strong id="ps_project">—</strong>
                        </div>
                        <div class="col-6 col-md-3 schedule-id-cell">
                            <small>Lote</small>
                            <strong id="ps_lot">—</strong>
                        </div>
                        <div class="col-12 col-md-3 schedule-id-cell border-right-0">
                            <small>Cliente</small>
                            <strong id="ps_customer">—</strong>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-4 mb-2">
                        <div class="schedule-money-card money-primary">
                            <span>Financiado</span>
                            <strong id="ps_financed">S/ 0.00</strong>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="schedule-money-card money-success">
                            <span>Pagado</span>
                            <strong id="ps_paid">S/ 0.00</strong>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="schedule-money-card money-danger">
                            <span>Deuda actual</span>
                            <strong id="ps_pending">S/ 0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="schedule-panel mb-3">
                    <div class="schedule-panel-title d-flex justify-content-between align-items-center flex-wrap">
                        <span><i class="fas fa-wallet text-success mr-2"></i>Resumen financiero</span>
                        <span class="schedule-progress-badge" id="ps_progress_text">0% Pagado</span>
                    </div>
                    <div class="schedule-metrics">
                        <div><small>Precio original</small><strong id="rf_original">S/ 0.00</strong></div>
                        <div><small>Amortizado</small><strong id="rf_amortizado">S/ 0.00</strong></div>
                        <div><small>Mora</small><strong id="rf_mora">S/ 0.00</strong></div>
                        <div><small>Descuentos</small><strong id="rf_descuentos">S/ 0.00</strong></div>
                        <div><small>Total real</small><strong id="rf_real">S/ 0.00</strong></div>
                        <div><small>Total pagado</small><strong id="rf_pagado" class="text-success">S/ 0.00</strong></div>
                        <div><small>Deuda</small><strong id="rf_deuda" class="text-danger">S/ 0.00</strong></div>
                    </div>
                    <div class="progress schedule-progress mt-2">
                        <div class="progress-bar bg-success" id="ps_progress_bar" style="width:0%;"></div>
                    </div>
                </div>

                <div class="schedule-panel mb-3">
                    <div class="schedule-panel-title">
                        <i class="fas fa-list-ul text-primary mr-2"></i>Cronograma de cuotas
                    </div>
                    <div class="table-responsive schedule-table-wrap">
                        <table class="table table-hover text-center mb-0 schedule-table" id="tablePaymentSchedule">
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
                                <tr><td colspan="9" class="py-4 text-muted">No hay cuotas generadas</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="schedule-panel">
                    <div class="schedule-panel-title">
                        <i class="fas fa-history text-secondary mr-2"></i>Historial financiero
                    </div>
                    <div class="table-responsive schedule-table-wrap">
                        <table class="table table-hover text-center mb-0 schedule-table">
                            <thead class="schedule-thead">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Operación</th>
                                    <th>Monto</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="financialHistoryBody">
                                <tr><td colspan="4" class="py-3 text-muted">No hay movimientos financieros</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer schedule-footer bg-white border-0 px-3 py-2">
                <button type="button" class="btn btn-danger btn-sm px-3" id="btnPdfSchedule">
                    <i class="fas fa-file-pdf mr-1"></i>Exportar PDF
                </button>
                <button type="button" class="btn btn-primary btn-sm px-3" id="btnPrintSchedule">
                    <i class="fas fa-print mr-1"></i>Imprimir
                </button>
                <button type="button" class="btn btn-light border btn-sm px-3" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    .schedule-scope .schedule-dialog {
        width: 94%;
        max-width: 1320px;
        margin: 16px auto;
    }

    .schedule-scope .schedule-modal-content {
        border-radius: 18px;
        overflow: hidden;
        max-height: 94vh;
    }

    .schedule-scope .schedule-header {
        background: linear-gradient(135deg, #0d2d52 0%, #165488 100%);
        color: #fff;
    }

    .schedule-scope .schedule-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        background: rgba(255,255,255,.14);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }

    .schedule-scope .schedule-subtitle {
        color: rgba(255,255,255,.72);
    }

    .schedule-scope .schedule-body {
        background: #f5f7fa;
        overflow-y: auto;
    }

    .schedule-scope .schedule-identification,
    .schedule-scope .schedule-panel {
        background: #fff;
        border: 1px solid #e5ebf1;
        border-radius: 13px;
        overflow: hidden;
    }

    .schedule-scope .schedule-id-cell {
        padding: 10px 12px;
        border-right: 1px solid #edf1f5;
        min-height: 58px;
    }

    .schedule-scope .schedule-id-cell small,
    .schedule-scope .schedule-money-card span,
    .schedule-scope .schedule-metrics small {
        display: block;
        color: #8693a2;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 3px;
    }

    .schedule-scope .schedule-id-cell strong {
        display: block;
        color: #23384c;
        font-size: 12px;
        line-height: 1.25;
        word-break: break-word;
    }

    .schedule-scope .schedule-money-card {
        background: #fff;
        border: 1px solid #e5ebf1;
        border-radius: 11px;
        padding: 10px 13px;
    }

    .schedule-scope .schedule-money-card strong {
        font-size: 18px;
        font-weight: 800;
    }

    .schedule-scope .money-primary strong { color: #0783d4; }
    .schedule-scope .money-success strong { color: #16a34a; }
    .schedule-scope .money-danger strong { color: #e33b49; }

    .schedule-scope .schedule-panel-title {
        padding: 9px 12px;
        border-bottom: 1px solid #edf1f5;
        color: #293f54;
        font-size: 12px;
        font-weight: 800;
    }

    .schedule-scope .schedule-progress-badge {
        background: #eaf4ff;
        color: #087cd0;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 9px;
        font-weight: 800;
    }

    .schedule-scope .schedule-metrics {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
    }

    .schedule-scope .schedule-metrics > div {
        padding: 9px 10px;
        border-right: 1px solid #edf1f5;
    }

    .schedule-scope .schedule-metrics > div:last-child {
        border-right: 0;
    }

    .schedule-scope .schedule-metrics strong {
        font-size: 12px;
        color: #263a4e;
    }

    .schedule-scope .schedule-progress {
        height: 7px;
        margin: 0 12px 10px;
        border-radius: 999px;
        background: #e8edf2;
    }

    .schedule-scope .schedule-table-wrap {
        overflow-x: auto;
    }

    .schedule-scope .schedule-table {
        min-width: 900px;
    }

    .schedule-scope .schedule-thead {
        background: #f0f4f8;
    }

    .schedule-scope .schedule-thead th {
        border: 0;
        border-bottom: 1px solid #e2e8ef;
        color: #40556a;
        font-size: 10px;
        font-weight: 800;
        padding: 8px 7px;
        white-space: nowrap;
    }

    .schedule-scope .schedule-table tbody td {
        font-size: 10.5px;
        padding: 7px !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    .schedule-scope .schedule-table .badge {
        font-size: 9px;
        padding: 5px 8px;
        border-radius: 999px;
    }

    .schedule-scope .schedule-footer {
        gap: 6px;
    }

    /* Contenedor temporal utilizado por html2canvas para PDF completo. */
    .schedule-export-host {
        position: absolute !important;
        left: -100000px !important;
        top: 0 !important;
        width: 1280px !important;
        background: #fff !important;
        z-index: -1 !important;
    }

    .schedule-export-host .schedule-modal-content {
        width: 1280px !important;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .schedule-export-host .schedule-body,
    .schedule-export-host .schedule-table-wrap,
    .schedule-export-host .table-responsive {
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }

    .schedule-export-host .schedule-footer,
    .schedule-export-host .close {
        display: none !important;
    }

    @media (max-width: 767.98px) {
        .schedule-scope .schedule-dialog {
            width: calc(100% - 12px);
            margin: 6px auto;
        }

        .schedule-scope .schedule-modal-content {
            max-height: 98vh;
        }

        .schedule-scope .schedule-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .schedule-scope .schedule-metrics > div {
            border-bottom: 1px solid #edf1f5;
        }

        .schedule-scope .schedule-title {
            font-size: 18px;
        }

        .schedule-scope .schedule-footer {
            flex-wrap: wrap;
        }
    }
</style>
