<style>
    #viewLotModal .modal-dialog {
        max-width: 980px;
    }

    #viewLotModal .modal-content {
        border-radius: 18px;
        overflow: hidden;
    }

    #viewLotModal .modal-header {
        background: linear-gradient(135deg, #0e2f55, #154f7f);
        color: #fff;
        border-bottom: 0;
        padding: 14px 20px;
    }

    #viewLotModal .modal-header .close {
        color: #fff;
        opacity: .9;
        text-shadow: none;
    }

    #viewLotModal .modal-body {
        padding: 0;
        background: #f5f8fc;
    }

    #viewLotModal .lot-summary {
        height: 100%;
        padding: 24px 20px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border-right: 1px solid #e6edf5;
    }

    #viewLotModal .lot-icon {
        width: 104px;
        height: 104px;
        margin: 0 auto 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0d6efd, #0759c7);
        color: #fff;
        font-size: 42px;
        box-shadow: 0 10px 24px rgba(13, 110, 253, .18);
    }

    #viewLotModal .lot-code {
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
        word-break: break-word;
    }

    #viewLotModal .lot-company {
        margin-top: 8px;
        color: #334155;
        font-weight: 700;
        font-size: 13px;
    }

    #viewLotModal .lot-project {
        color: #64748b;
        font-size: 13px;
    }

    #viewLotModal .summary-meta {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e8eef5;
        text-align: left;
    }

    #viewLotModal .summary-meta-item + .summary-meta-item {
        margin-top: 12px;
    }

    #viewLotModal .detail-content {
        padding: 20px;
    }

    #viewLotModal .detail-section {
        background: #fff;
        border: 1px solid #e6edf5;
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .035);
    }

    #viewLotModal .detail-section:last-child {
        margin-bottom: 0;
    }

    #viewLotModal .section-title {
        margin: 0 0 12px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    #viewLotModal .detail-label {
        display: block;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 3px;
    }

    #viewLotModal .detail-value {
        color: #1f2937;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.35;
        word-break: break-word;
    }

    #viewLotModal .price-box {
        border-radius: 12px;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid #e8eef5;
        height: 100%;
    }

    #viewLotModal .price-value {
        font-size: 19px;
        font-weight: 800;
        margin-top: 2px;
    }

    #viewLotModal .boundary-box {
        border: 1px solid #e8eef5;
        background: #fbfdff;
        border-radius: 10px;
        padding: 10px 12px;
        min-height: 58px;
    }

    #viewLotModal .system-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    @media (max-width: 767.98px) {
        #viewLotModal .lot-summary {
            border-right: 0;
            border-bottom: 1px solid #e6edf5;
        }

        #viewLotModal .detail-content {
            padding: 14px;
        }

        #viewLotModal .system-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="modal fade" id="viewLotModal" tabindex="-1" role="dialog" aria-labelledby="viewLotModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="viewLotModalLabel">
                    <i class="fas fa-eye mr-2"></i>
                    Información del Lote
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row no-gutters">

                    <div class="col-md-4">
                        <div class="lot-summary text-center">
                            <div class="lot-icon">
                                <i class="fas fa-map"></i>
                            </div>

                            <div id="vl_code" class="lot-code">—</div>
                            <div id="vl_company" class="lot-company">—</div>
                            <div id="vl_project" class="lot-project">—</div>

                            <div class="mt-3">
                                <span id="vl_status" class="badge badge-secondary rounded-pill py-2 px-3 text-capitalize">
                                    —
                                </span>
                            </div>

                            <div class="summary-meta">
                                <div class="summary-meta-item">
                                    <span class="detail-label">Registrado por</span>
                                    <div id="vl_created_by_summary" class="detail-value">—</div>
                                </div>

                                <div class="summary-meta-item">
                                    <span class="detail-label">Última actualización</span>
                                    <div id="vl_updated_at" class="detail-value">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="detail-content">

                            <section class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-info-circle text-primary mr-1"></i>
                                    Información general
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span class="detail-label">Empresa</span>
                                        <div id="vl_company_name" class="detail-value">—</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <span class="detail-label">Proyecto</span>
                                        <div id="vl_project_name" class="detail-value">—</div>
                                    </div>

                                    <div class="col-4 mb-2">
                                        <span class="detail-label">Manzana</span>
                                        <div id="vl_block" class="detail-value">—</div>
                                    </div>

                                    <div class="col-4 mb-2">
                                        <span class="detail-label">N.º de lote</span>
                                        <div id="vl_number" class="detail-value">—</div>
                                    </div>

                                    <div class="col-4 mb-2">
                                        <span class="detail-label">Área</span>
                                        <div class="detail-value">
                                            <span id="vl_area">—</span>
                                            <span id="vl_unit_measure"></span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-coins text-warning mr-1"></i>
                                    Información comercial
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-2 mb-md-0">
                                        <div class="price-box">
                                            <span class="detail-label">Precio contado</span>
                                            <div id="vl_cash_price" class="price-value text-success">S/ 0.00</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="price-box">
                                            <span class="detail-label">Precio financiado</span>
                                            <div id="vl_financed_price" class="price-value text-primary">S/ 0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-border-style text-info mr-1"></i>
                                    Colindancias
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="boundary-box">
                                            <span class="detail-label">Norte</span>
                                            <div id="vl_north_boundary" class="detail-value">—</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="boundary-box">
                                            <span class="detail-label">Sur</span>
                                            <div id="vl_south_boundary" class="detail-value">—</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="boundary-box">
                                            <span class="detail-label">Este</span>
                                            <div id="vl_east_boundary" class="detail-value">—</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="boundary-box">
                                            <span class="detail-label">Oeste</span>
                                            <div id="vl_west_boundary" class="detail-value">—</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="detail-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <h6 class="section-title">
                                            <i class="fas fa-sticky-note text-secondary mr-1"></i>
                                            Observación
                                        </h6>
                                        <div id="vl_observation" class="detail-value">—</div>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="section-title">
                                            <i class="fas fa-database text-secondary mr-1"></i>
                                            Sistema
                                        </h6>

                                        <div class="system-grid">
                                            <div>
                                                <span class="detail-label">ID</span>
                                                <div id="vl_id" class="detail-value">—</div>
                                            </div>
                                            <div>
                                                <span class="detail-label">Registro</span>
                                                <div id="vl_created_at" class="detail-value">—</div>
                                            </div>
                                            <div>
                                                <span class="detail-label">Creado por</span>
                                                <div id="vl_created_by_user" class="detail-value">—</div>
                                            </div>
                                            <div>
                                                <span class="detail-label">Editado por</span>
                                                <div id="vl_updated_by_user" class="detail-value">—</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
