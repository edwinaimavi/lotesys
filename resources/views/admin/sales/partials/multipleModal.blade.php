<!-- MODAL VENTA MULTIPLE -->
<div class="modal fade" id="multiSaleModal" tabindex="-1" role="dialog" aria-labelledby="multiSaleModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg multi-sale-modal">

            <div class="modal-header multi-sale-header align-items-center">
                <div class="d-flex align-items-center min-w-0">
                    <div class="multi-sale-header-icon mr-3">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="modal-title mb-0" id="multiSaleModalLabel">Venta de varios lotes</h5>
                        <small>Una sola venta, un solo cronograma y varios lotes del mismo proyecto.</small>
                    </div>
                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="multiSaleForm" autocomplete="off">
                @csrf

                <div class="modal-body multi-sale-body">
                    <div class="row">
                        <div class="col-lg-7 mb-3 mb-lg-0">
                            <div class="multi-sale-section mb-3">
                                <div class="multi-sale-section-title">
                                    <i class="fas fa-file-signature mr-2"></i>Datos de la operación
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="multi_sale_code">CÓDIGO <span class="text-danger">*</span></label>
                                        <input type="text" id="multi_sale_code" name="sale_code"
                                            class="form-control form-control-sm" readonly>
                                        <span class="invalid-feedback" id="multi_sale_code-error"></span>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="multi_sale_date">FECHA VENTA <span class="text-danger">*</span></label>
                                        <input type="date" id="multi_sale_date" name="sale_date"
                                            class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                        <span class="invalid-feedback" id="multi_sale_date-error"></span>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="multi_status">ESTADO</label>
                                        <select id="multi_status" name="status" class="form-control form-control-sm">
                                            <option value="activo">Activo</option>
                                            <option value="cancelado">Cancelado</option>
                                            <option value="rescindido">Rescindido</option>
                                            <option value="finalizado">Finalizado</option>
                                        </select>
                                        <span class="invalid-feedback" id="multi_status-error"></span>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-7">
                                        <label for="multi_customer_id">CLIENTE <span class="text-danger">*</span></label>
                                        <select id="multi_customer_id" name="customer_id" class="form-control form-control-sm">
                                            <option value="">Seleccione un cliente</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">
                                                    @if ($customer->person_type == 'natural')
                                                        {{ trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) }}
                                                    @else
                                                        {{ $customer->business_name }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="invalid-feedback" id="multi_customer_id-error"></span>
                                    </div>

                                    <div class="form-group col-md-5">
                                        <label for="multi_sale_type">TIPO DE VENTA <span class="text-danger">*</span></label>
                                        <select id="multi_sale_type" name="sale_type" class="form-control form-control-sm">
                                            <option value="financiado">Financiado</option>
                                            <option value="contado">Contado</option>
                                        </select>
                                        <span class="invalid-feedback" id="multi_sale_type-error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="multi-sale-section">
                                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                    <div class="multi-sale-section-title mb-0">
                                        <i class="fas fa-map-marked-alt mr-2"></i>Lotes de la venta
                                    </div>
                                    <span class="badge badge-light border" id="multi_lot_counter">0 seleccionados</span>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="multi_lot_ids">SELECCIONE 2 O MÁS LOTES <span class="text-danger">*</span></label>
                                    <select id="multi_lot_ids" name="lot_ids[]" class="form-control" multiple></select>
                                    <span class="invalid-feedback d-block" id="multi_lot_ids-error"></span>
                                    <small class="text-muted">Todos los lotes deben pertenecer al mismo proyecto.</small>
                                </div>

                                <div id="multi_selected_lots" class="multi-selected-lots">
                                    <div class="multi-empty-lots">
                                        <i class="fas fa-map mr-2"></i>Seleccione los lotes que formarán parte de la venta.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="multi-sale-summary mb-3">
                                <div class="multi-summary-head">
                                    <span>Resumen de la venta</span>
                                    <strong id="multi_summary_count">0 lotes</strong>
                                </div>

                                <div class="multi-summary-total">
                                    <small>PRECIO TOTAL</small>
                                    <strong id="multi_total_price_label">S/ 0.00</strong>
                                </div>

                                <input type="hidden" id="multi_lot_price" name="lot_price" value="0">
                                <input type="hidden" id="multi_balance_finance" name="balance_finance" value="0">
                                <input type="hidden" id="multi_monthly_payment" name="monthly_payment" value="0">
                            </div>

                            <div class="multi-sale-section mb-3">
                                <div class="multi-sale-section-title">
                                    <i class="fas fa-coins mr-2"></i>Financiamiento
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="multi_initial_payment">INICIAL <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" id="multi_initial_payment"
                                            name="initial_payment" class="form-control form-control-sm" value="0">
                                        <span class="invalid-feedback" id="multi_initial_payment-error"></span>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="multi_installments_count">CUOTAS <span class="text-danger">*</span></label>
                                        <input type="number" min="1" id="multi_installments_count"
                                            name="installments_count" class="form-control form-control-sm" value="1">
                                        <span class="invalid-feedback" id="multi_installments_count-error"></span>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="multi_payment_mode">MODO DE CÁLCULO</label>
                                        <select id="multi_payment_mode" name="payment_mode" class="form-control form-control-sm">
                                            <option value="automatico">Automático</option>
                                            <option value="personalizado">Personalizado</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-6 d-none" id="multi_custom_payment_container">
                                        <label for="multi_custom_payment">CUOTA PERSONALIZADA</label>
                                        <input type="number" step="0.01" min="1" id="multi_custom_payment"
                                            name="custom_payment" class="form-control form-control-sm">
                                        <span class="invalid-feedback" id="multi_custom_payment-error"></span>
                                    </div>
                                </div>

                                <div class="multi-finance-preview mb-3">
                                    <div>
                                        <small>SALDO</small>
                                        <strong id="multi_balance_label">S/ 0.00</strong>
                                    </div>
                                    <div>
                                        <small>CUOTA ESTIMADA</small>
                                        <strong id="multi_monthly_label">S/ 0.00</strong>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="multi_interest_rate">INTERÉS %</label>
                                        <input type="number" step="0.01" min="0" id="multi_interest_rate"
                                            name="interest_rate" class="form-control form-control-sm" value="0">
                                        <span class="invalid-feedback" id="multi_interest_rate-error"></span>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="multi_payment_day">DÍA PAGO</label>
                                        <input type="number" min="1" max="31" id="multi_payment_day"
                                            name="payment_day" class="form-control form-control-sm">
                                        <span class="invalid-feedback" id="multi_payment_day-error"></span>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="multi_first_payment_date">PRIMER PAGO</label>
                                    <input type="date" id="multi_first_payment_date" name="first_payment_date"
                                        class="form-control form-control-sm">
                                    <span class="invalid-feedback" id="multi_first_payment_date-error"></span>
                                </div>
                            </div>

                            <div class="multi-sale-section mb-3">
                                <div class="form-group mb-0">
                                    <label for="multi_late_fee_setting_id">CONFIGURACIÓN MORA</label>
                                    <select id="multi_late_fee_setting_id" name="late_fee_setting_id"
                                        class="form-control form-control-sm">
                                        <option value="">Sin mora</option>
                                        @foreach ($lateFeeSettings as $setting)
                                            @if ($setting->status == 'activo')
                                                <option value="{{ $setting->id }}">
                                                    Gracia: {{ $setting->grace_days }} días · Mora: S/ {{ number_format($setting->daily_late_fee, 2) }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="multi-sale-section">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="multi_is_legacy_sale"
                                        name="is_legacy_sale" value="1">
                                    <label class="custom-control-label" for="multi_is_legacy_sale">
                                        Venta histórica / regularización
                                    </label>
                                </div>

                                <div id="multi_legacy_fields" class="mt-3 d-none">
                                    <div class="form-group">
                                        <label for="multi_collection_rules_start_date">FECHA INICIO MORA / RESCISIÓN</label>
                                        <input type="date" id="multi_collection_rules_start_date"
                                            name="collection_rules_start_date" class="form-control form-control-sm" disabled>
                                        <span class="invalid-feedback" id="multi_collection_rules_start_date-error"></span>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="multi_legacy_observation">OBSERVACIÓN</label>
                                        <textarea id="multi_legacy_observation" name="legacy_observation"
                                            class="form-control form-control-sm" rows="2" disabled></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer multi-sale-footer">
                    <div class="mr-auto text-muted small">
                        <i class="fas fa-shield-alt mr-1"></i>
                        La venta simple actual no se modifica.
                    </div>
                    <button type="button" class="btn btn-light border" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cerrar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSaveMultiSale">
                        <i class="fas fa-layer-group mr-1"></i>Guardar venta múltiple
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #multiSaleModal .modal-dialog { max-width: 1180px; }
    #multiSaleModal .multi-sale-modal { border-radius: 18px; overflow: hidden; }
    #multiSaleModal .multi-sale-header {
        color: #fff;
        border: 0;
        background: linear-gradient(135deg, #25292d 0%, #394037 70%, #63891d 100%);
    }
    #multiSaleModal .multi-sale-header small { color: rgba(255,255,255,.72); }
    #multiSaleModal .multi-sale-header .close { opacity: 1; text-shadow: none; }
    #multiSaleModal .multi-sale-header-icon {
        width: 42px; height: 42px; min-width: 42px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.12);
    }
    #multiSaleModal .multi-sale-body {
        max-height: 76vh;
        overflow-y: auto;
        background: #f5f7f4;
        padding: 16px;
    }
    #multiSaleModal .multi-sale-section,
    #multiSaleModal .multi-sale-summary {
        background: #fff;
        border: 1px solid #e5e9e2;
        border-radius: 14px;
        padding: 14px;
    }
    #multiSaleModal .multi-sale-section-title {
        color: #343b35;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 12px;
    }
    #multiSaleModal .multi-sale-section-title i { color: #789f2c; }
    #multiSaleModal label {
        color: #626b63;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .035em;
        margin-bottom: 5px;
    }
    #multiSaleModal .select2-container { width: 100% !important; }
    #multiSaleModal .select2-selection--multiple { min-height: 38px; }
    #multiSaleModal .multi-selected-lots {
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid #e8ece5;
        border-radius: 12px;
        background: #fafbf9;
    }
    #multiSaleModal .multi-empty-lots {
        padding: 24px 16px;
        text-align: center;
        color: #929a92;
        font-size: 12px;
    }
    #multiSaleModal .multi-lot-row {
        display: grid;
        grid-template-columns: minmax(0,1fr) auto;
        gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px solid #edf0eb;
    }
    #multiSaleModal .multi-lot-row:last-child { border-bottom: 0; }
    #multiSaleModal .multi-lot-name { font-size: 12px; font-weight: 800; color: #303731; }
    #multiSaleModal .multi-lot-meta { margin-top: 2px; color: #899189; font-size: 10px; }
    #multiSaleModal .multi-lot-price { white-space: nowrap; color: #668b20; font-size: 12px; font-weight: 800; }
    #multiSaleModal .multi-summary-head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        color: #6f776f; font-size: 11px; font-weight: 700;
    }
    #multiSaleModal .multi-summary-total {
        margin-top: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        background: linear-gradient(135deg, #f4f8ec, #fbfcf8);
        border-left: 4px solid #87bc27;
    }
    #multiSaleModal .multi-summary-total small { display: block; color: #7f887f; font-size: 10px; font-weight: 800; }
    #multiSaleModal .multi-summary-total strong { display: block; margin-top: 2px; color: #537719; font-size: 24px; }
    #multiSaleModal .multi-finance-preview {
        display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
    }
    #multiSaleModal .multi-finance-preview > div {
        padding: 10px 12px; border: 1px solid #edf0eb; border-radius: 10px; background: #fafbf9;
    }
    #multiSaleModal .multi-finance-preview small { display: block; color: #909890; font-size: 9px; font-weight: 800; }
    #multiSaleModal .multi-finance-preview strong { display: block; margin-top: 3px; color: #333a34; font-size: 15px; }
    #multiSaleModal .multi-sale-footer { background: #fff; border-top: 1px solid #e8ece5; }
    @media (max-width: 767.98px) {
        #multiSaleModal .modal-dialog { margin: 8px; }
        #multiSaleModal .multi-sale-body { max-height: calc(100vh - 145px); }
        #multiSaleModal .multi-finance-preview { grid-template-columns: 1fr; }
        #multiSaleModal .multi-sale-footer .mr-auto { width: 100%; margin-bottom: 8px; }
    }
</style>
