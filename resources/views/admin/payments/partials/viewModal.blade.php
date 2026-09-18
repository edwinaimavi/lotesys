<!-- VIEW PAYMENT MODAL -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog" aria-labelledby="viewPaymentModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg overflow-hidden payment-view-modal">

            <div class="modal-header payment-view-header px-4 py-3 align-items-center">
                <div class="d-flex align-items-center">
                    <div class="payment-view-header-icon mr-3">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="viewPaymentModalLabel">
                            Información del Pago
                        </h5>
                        <small class="payment-view-subtitle">Detalle financiero y referencia inmobiliaria</small>
                    </div>
                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body payment-view-body p-3 p-md-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 payment-view-topbar">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <div class="payment-view-main-icon mr-3">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase payment-view-label">Pago</small>
                            <div class="d-flex align-items-center flex-wrap">
                                <h4 class="font-weight-bold mb-0 mr-2">#<span id="vp_id">—</span></h4>
                                <span id="vp_status" class="badge badge-success px-3 py-2">ACTIVO</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-md-right">
                        <small class="text-muted d-block">Fecha de pago</small>
                        <strong id="vp_payment_date">—</strong>
                    </div>
                </div>

                <div class="row payment-view-reference-row">
                    <div class="col-lg-4 mb-3">
                        <div class="payment-view-card h-100">
                            <div class="payment-view-card-icon text-primary"><i class="fas fa-file-signature"></i></div>
                            <div>
                                <small class="payment-view-label">Venta / Cliente</small>
                                <div id="vp_sale" class="font-weight-bold text-dark">—</div>
                                <small id="vp_customer" class="text-muted d-block">—</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="payment-view-card h-100">
                            <div class="payment-view-card-icon text-info"><i class="fas fa-building"></i></div>
                            <div>
                                <small class="payment-view-label">Empresa</small>
                                <div id="vp_company" class="font-weight-bold text-dark">—</div>
                                <small class="text-muted">RUC: <span id="vp_company_ruc">—</span></small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="payment-view-card h-100">
                            <div class="payment-view-card-icon text-success"><i class="fas fa-map-marked-alt"></i></div>
                            <div>
                                <small class="payment-view-label">Inmueble</small>
                                <div id="vp_project" class="font-weight-bold text-dark">—</div>
                                <small class="text-muted d-block">
                                    <span id="vp_block">—</span> · Lote <span id="vp_lot_number">—</span>
                                </small>
                                <small id="vp_lot_code" class="text-primary font-weight-bold d-block">—</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-view-section mb-3">
                    <div class="payment-view-section-title">
                        <i class="fas fa-wallet mr-2"></i>Resumen financiero
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="payment-view-metric payment-view-metric-primary">
                                <small>Monto pagado</small>
                                <strong id="vp_amount">S/ 0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="payment-view-metric payment-view-metric-danger">
                                <small>Mora pagada</small>
                                <strong id="vp_late_fee">S/ 0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="payment-view-metric payment-view-metric-success">
                                <small>Descuento</small>
                                <strong id="vp_discount">S/ 0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <div class="payment-view-section h-100">
                            <div class="payment-view-section-title">
                                <i class="fas fa-list-ul mr-2"></i>Detalle del pago
                            </div>

                            <div class="row payment-view-detail-grid">
                                <div class="col-md-6">
                                    <div class="payment-view-detail-item">
                                        <span>Cuota aplicada</span>
                                        <strong id="vp_schedule">—</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-view-detail-item">
                                        <span>Tipo de pago</span>
                                        <strong id="vp_payment_type">—</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-view-detail-item">
                                        <span>Método de pago</span>
                                        <strong id="vp_payment_method">—</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-view-detail-item">
                                        <span>N.º operación</span>
                                        <strong id="vp_operation_number">—</strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="payment-view-detail-item payment-view-observation">
                                        <span>Observación</span>
                                        <strong id="vp_observation">Sin observación</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="payment-view-section h-100">
                            <div class="payment-view-section-title">
                                <i class="fas fa-user-clock mr-2"></i>Información del sistema
                            </div>

                            <div class="payment-view-system-row">
                                <span>Registrado por</span>
                                <strong id="vp_created_by">—</strong>
                            </div>
                            <div class="payment-view-system-row">
                                <span>Fecha de registro</span>
                                <strong id="vp_created_at">—</strong>
                            </div>
                            <div class="payment-view-system-row">
                                <span>Última edición por</span>
                                <strong id="vp_updated_by">—</strong>
                            </div>
                            <div class="payment-view-system-row border-0 pb-0">
                                <span>Última actualización</span>
                                <strong id="vp_updated_at">—</strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer border-0 payment-view-footer py-2 px-4">
                <button type="button" class="btn btn-light border px-4" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>

        </div>

    </div>
</div>

<style>
    .payment-view-modal {
        border-radius: 18px;
        background: #f7f9fc;
    }

    .payment-view-header {
        color: #fff;
        border: 0;
        background: linear-gradient(110deg, #102f52, #18588c);
    }

    .payment-view-header .close {
        opacity: .9;
        text-shadow: none;
    }

    .payment-view-header-icon,
    .payment-view-main-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .payment-view-header-icon {
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, .14);
    }

    .payment-view-subtitle {
        color: rgba(255, 255, 255, .75);
    }

    .payment-view-body {
        background: #f5f7fa;
    }

    .payment-view-topbar {
        padding: 12px 14px;
        background: #fff;
        border: 1px solid #e7ebf0;
        border-radius: 14px;
    }

    .payment-view-main-icon {
        width: 48px;
        height: 48px;
        color: #fff;
        font-size: 20px;
        background: linear-gradient(135deg, #28a745, #198754);
        box-shadow: 0 5px 14px rgba(40, 167, 69, .18);
    }

    .payment-view-card,
    .payment-view-section {
        background: #fff;
        border: 1px solid #e6ebf1;
        border-radius: 14px;
    }

    .payment-view-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 14px;
    }

    .payment-view-card-icon {
        width: 34px;
        min-width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f5f8fb;
        border-radius: 10px;
    }

    .payment-view-label,
    .payment-view-detail-item span,
    .payment-view-system-row span,
    .payment-view-metric small {
        display: block;
        color: #7c8795;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .025em;
    }

    .payment-view-section {
        padding: 14px;
    }

    .payment-view-section-title {
        margin-bottom: 12px;
        color: #32465a;
        font-size: 14px;
        font-weight: 700;
    }

    .payment-view-metric {
        padding: 11px 13px;
        border-radius: 12px;
        background: #f8fafc;
        border-left: 4px solid #adb5bd;
    }

    .payment-view-metric strong {
        display: block;
        margin-top: 2px;
        font-size: 20px;
    }

    .payment-view-metric-primary { border-left-color: #0d6efd; }
    .payment-view-metric-primary strong { color: #0d6efd; }
    .payment-view-metric-danger { border-left-color: #dc3545; }
    .payment-view-metric-danger strong { color: #dc3545; }
    .payment-view-metric-success { border-left-color: #28a745; }
    .payment-view-metric-success strong { color: #28a745; }

    .payment-view-detail-item {
        padding: 8px 4px 10px;
        border-bottom: 1px solid #edf0f3;
    }

    .payment-view-detail-item strong {
        display: block;
        margin-top: 3px;
        color: #1f2d3d;
        font-size: 13px;
    }

    .payment-view-observation {
        border-bottom: 0;
        padding-bottom: 2px;
    }

    .payment-view-system-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        padding: 9px 0;
        border-bottom: 1px solid #edf0f3;
    }

    .payment-view-system-row strong {
        color: #263747;
        font-size: 12px;
        text-align: right;
    }

    .payment-view-footer {
        background: #fff;
    }

    @media (max-width: 767.98px) {
        .payment-view-modal {
            border-radius: 12px;
        }

        .payment-view-system-row {
            display: block;
        }

        .payment-view-system-row strong {
            display: block;
            margin-top: 3px;
            text-align: left;
        }
    }
</style>
