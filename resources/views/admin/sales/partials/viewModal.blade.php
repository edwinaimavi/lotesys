<!-- VIEW SALE MODAL -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog" aria-labelledby="viewSaleModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg sale-detail-modal">

            <div class="modal-header sale-detail-header border-0 px-4 py-3">
                <div class="d-flex align-items-center min-w-0">
                    <div class="sale-detail-header-icon mr-3">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="modal-title font-weight-bold mb-0" id="viewSaleModalLabel">Información de la Venta</h4>
                        <small class="sale-detail-subtitle">Resumen comercial y financiero</small>
                    </div>
                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="opacity:1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body sale-detail-body p-3 p-md-4">

                <div class="sale-detail-hero mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <small class="sale-detail-label">Código de venta</small>
                            <div id="vs_codigo_venta" class="sale-detail-code">VTA00001</div>
                        </div>
                        <div class="col-md-5 mb-3 mb-md-0">
                            <small class="sale-detail-label">Cliente</small>
                            <div id="vs_cliente" class="sale-detail-client">Cliente</div>
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <small class="sale-detail-label">Fecha</small>
                            <div id="vs_fecha_venta" class="sale-detail-value">—</div>
                        </div>
                        <div class="col-md-2 text-md-right">
                            <span id="vs_estado_badge" class="badge badge-success sale-detail-status">Activo</span>
                        </div>
                    </div>
                </div>

                <div class="sale-detail-section mb-3">
                    <div class="sale-detail-section-title">
                        <i class="fas fa-map-marked-alt mr-2"></i><span id="vs_location_title">Ubicación e identificación del lote</span>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="sale-detail-item h-100">
                                <small>Empresa</small>
                                <strong id="vs_empresa">—</strong>
                                <span>RUC: <span id="vs_empresa_ruc">—</span></span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="sale-detail-item h-100">
                                <small>Proyecto</small>
                                <strong id="vs_proyecto">—</strong>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="sale-detail-item h-100">
                                <small>Manzana</small>
                                <strong id="vs_manzana">—</strong>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="sale-detail-item h-100">
                                <small>N.º de lote</small>
                                <strong id="vs_lote_numero">—</strong>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="sale-detail-item h-100">
                                <small>Código del lote</small>
                                <strong id="vs_lote_codigo" class="text-primary">—</strong>
                            </div>
                        </div>
                    </div>

                    <div id="vs_lots_multiple_wrapper" class="mt-2 d-none">
                        <small class="sale-detail-label mb-2">Lotes incluidos en esta venta</small>
                        <div id="vs_lots_multiple" class="sale-multi-lots-grid"></div>
                    </div>
                </div>

                <div class="sale-detail-section mb-3">
                    <div class="sale-detail-section-title">
                        <i class="fas fa-wallet mr-2"></i>Resumen financiero
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="sale-money-card sale-money-primary">
                                <span id="vs_precio_label">Precio del lote</span>
                                <strong id="vs_precio_lote">S/ 0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="sale-money-card sale-money-success">
                                <span>Inicial</span>
                                <strong id="vs_inicial">S/ 0.00</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="sale-money-card sale-money-danger">
                                <span>Saldo a financiar</span>
                                <strong id="vs_saldo_financiar">S/ 0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sale-detail-section mb-3">
                    <div class="sale-detail-section-title">
                        <i class="fas fa-calculator mr-2"></i>Condiciones de financiamiento
                    </div>

                    <div class="row sale-finance-grid">
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Cuotas</small>
                            <strong id="vs_cantidad_cuotas">—</strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Cuota mensual</small>
                            <strong id="vs_cuota_mensual">—</strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Interés</small>
                            <strong id="vs_tasa_interes">—</strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Primer pago</small>
                            <strong id="vs_fecha_primer_pago">—</strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Día de pago</small>
                            <strong id="vs_dia_pago">—</strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2 mb-2">
                            <small>Estado</small>
                            <strong id="vs_estado_text">—</strong>
                        </div>
                    </div>
                </div>

                <div class="sale-system-strip">
                    <div><small>ID Venta</small><strong id="vs_id">—</strong></div>
                    <div><small>Registrado</small><strong id="vs_created_at">—</strong></div>
                    <div><small>Usuario creador</small><strong id="vs_created_by_user">—</strong></div>
                    <div><small>Última edición</small><strong id="vs_updated_by_user">—</strong></div>
                    <div class="d-none"><span id="vs_cliente_nombre">—</span><span id="vs_created_by">—</span><span id="vs_updated_at">—</span></div>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-3">
                <button type="button" class="btn btn-light border px-4" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    #viewSaleModal .modal-dialog {
        max-width: 1120px;
    }

    #viewSaleModal .sale-detail-modal {
        border-radius: 18px;
        overflow: hidden;
    }

    #viewSaleModal .sale-detail-header {
        background: linear-gradient(135deg, #0d2d52 0%, #165488 100%);
        color: #fff;
    }

    #viewSaleModal .sale-detail-header-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.14);
        font-size: 18px;
    }

    #viewSaleModal .sale-detail-subtitle {
        color: rgba(255,255,255,.72);
    }

    #viewSaleModal .sale-detail-body {
        background: #f6f8fb;
        max-height: 78vh;
        overflow-y: auto;
    }

    #viewSaleModal .sale-detail-hero,
    #viewSaleModal .sale-detail-section,
    #viewSaleModal .sale-system-strip {
        background: #fff;
        border: 1px solid #e6ebf2;
        border-radius: 14px;
    }

    #viewSaleModal .sale-detail-hero {
        padding: 14px 16px;
        box-shadow: 0 4px 14px rgba(15, 45, 82, .05);
    }

    #viewSaleModal .sale-detail-label,
    #viewSaleModal .sale-detail-item small,
    #viewSaleModal .sale-finance-grid small,
    #viewSaleModal .sale-system-strip small {
        display: block;
        font-size: 10px;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #8592a3;
        font-weight: 700;
        margin-bottom: 4px;
    }

    #viewSaleModal .sale-detail-code {
        font-size: 22px;
        font-weight: 800;
        color: #17324d;
    }

    #viewSaleModal .sale-detail-client {
        font-size: 15px;
        font-weight: 800;
        color: #1f2937;
    }

    #viewSaleModal .sale-detail-value {
        font-size: 14px;
        font-weight: 700;
        color: #26384a;
    }

    #viewSaleModal .sale-detail-status {
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 11px;
        text-transform: uppercase;
    }

    #viewSaleModal .sale-detail-section {
        padding: 14px;
    }

    #viewSaleModal .sale-detail-section-title {
        font-size: 13px;
        font-weight: 800;
        color: #274663;
        margin-bottom: 10px;
    }

    #viewSaleModal .sale-detail-section-title i {
        color: #1987d4;
    }

    #viewSaleModal .sale-detail-item,
    #viewSaleModal .sale-finance-grid > div {
        background: #f8fafc;
        border: 1px solid #edf1f5;
        border-radius: 10px;
        padding: 10px 12px;
    }

    #viewSaleModal .sale-detail-item strong,
    #viewSaleModal .sale-finance-grid strong {
        display: block;
        color: #223548;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
        word-break: break-word;
    }

    #viewSaleModal .sale-detail-item span {
        display: block;
        margin-top: 3px;
        color: #8a96a4;
        font-size: 10px;
    }

    #viewSaleModal .sale-money-card {
        border-radius: 11px;
        padding: 12px 14px;
        border: 1px solid #e7edf3;
        background: #fff;
    }

    #viewSaleModal .sale-money-card span {
        display: block;
        font-size: 10px;
        font-weight: 700;
        color: #8693a1;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    #viewSaleModal .sale-money-card strong {
        font-size: 20px;
        font-weight: 800;
    }

    #viewSaleModal .sale-money-primary strong { color: #087ceb; }
    #viewSaleModal .sale-money-success strong { color: #16a34a; }
    #viewSaleModal .sale-money-danger strong { color: #e63846; }

    #viewSaleModal .sale-system-strip {
        display: grid;
        grid-template-columns: .7fr 1.2fr 1fr 1fr;
        gap: 1px;
        padding: 0;
        overflow: hidden;
    }

    #viewSaleModal .sale-system-strip > div {
        padding: 10px 12px;
        background: #fff;
        border-right: 1px solid #edf1f5;
    }

    #viewSaleModal .sale-system-strip > div:last-child {
        border-right: 0;
    }

    #viewSaleModal .sale-system-strip strong {
        display: block;
        font-size: 12px;
        color: #34495e;
    }


    #viewSaleModal .sale-multi-lots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 8px;
    }

    #viewSaleModal .sale-multi-lot-chip {
        padding: 9px 10px;
        border: 1px solid #e6ebf0;
        border-radius: 10px;
        background: #f9fbf8;
    }

    #viewSaleModal .sale-multi-lot-chip strong {
        display: block;
        color: #344236;
        font-size: 12px;
        line-height: 1.25;
    }

    #viewSaleModal .sale-multi-lot-chip span {
        display: block;
        margin-top: 3px;
        color: #879087;
        font-size: 10px;
    }

    @media (max-width: 767.98px) {
        #viewSaleModal .modal-dialog {
            margin: 8px;
        }

        #viewSaleModal .sale-detail-body {
            max-height: calc(100vh - 150px);
        }

        #viewSaleModal .sale-system-strip {
            grid-template-columns: 1fr 1fr;
        }

        #viewSaleModal .sale-detail-code {
            font-size: 19px;
        }
    }
</style>
