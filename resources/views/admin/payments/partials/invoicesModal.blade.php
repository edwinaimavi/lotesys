<style>
    #generateInvoiceModal .modal-dialog {
        max-width: 1060px;
    }

    #generateInvoiceModal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(15, 23, 42, .18);
    }

    #generateInvoiceModal .modal-header {
        padding: .85rem 1rem;
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
        border: 0;
    }

    #generateInvoiceModal .modal-body {
        background: #f4f7fb;
        padding: .85rem;
    }

    #generateInvoiceModal .modal-footer {
        padding: .8rem 1rem;
        background: #fff;
        border-top: 1px solid #eef2f7;
    }

    #generateInvoiceModal .invoice-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
        overflow: hidden;
        margin-bottom: .75rem;
    }

    #generateInvoiceModal .invoice-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf2f7;
        padding: .65rem .9rem;
        font-size: .92rem;
        font-weight: 700;
        color: #1f2937;
    }

    #generateInvoiceModal .invoice-card .card-body {
        padding: .85rem .9rem;
    }

    #generateInvoiceModal label {
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: .3rem;
    }

    #generateInvoiceModal .form-control,
    #generateInvoiceModal .custom-select {
        height: 38px;
        font-size: .92rem;
        border-radius: 10px;
    }

    #generateInvoiceModal textarea.form-control {
        min-height: 80px;
        height: auto;
    }

    #generateInvoiceModal .invoice-side {
        position: sticky;
        top: 0;
    }

    #generateInvoiceModal .side-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
        padding: .9rem;
        margin-bottom: .75rem;
    }

    #generateInvoiceModal .side-title {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .2rem;
    }

    #generateInvoiceModal .side-value {
        font-size: .92rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    #generateInvoiceModal .amount-main {
        font-size: 1.45rem;
        line-height: 1.1;
        font-weight: 800;
        color: #16a34a;
    }

    #generateInvoiceModal .summary-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
        padding: .95rem .9rem;
    }

    #generateInvoiceModal .summary-label {
        font-size: .78rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .25rem;
    }

    #generateInvoiceModal .summary-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }

    #generateInvoiceModal .summary-value.total {
        color: #16a34a;
        font-size: 1.3rem;
    }

    #generateInvoiceModal .list-check {
        margin: 0;
        padding-left: 1rem;
        color: #334155;
        font-size: .88rem;
    }

    #generateInvoiceModal .badge-soft {
        display: inline-block;
        padding: .35rem .65rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
    }

    @media (max-width: 991.98px) {
        #generateInvoiceModal .invoice-side {
            position: static;
        }
    }
</style>

<div class="modal fade" id="generateInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header text-white">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold">Facturación Electrónica</h5>
                        <small style="opacity:.92;">Emisión de comprobantes SUNAT</small>
                    </div>
                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">

                    <!-- PANEL IZQUIERDO -->
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <div class="invoice-side">

                            <div class="side-box text-center">
                                <div class="mb-3">
                                    <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:88px;height:88px;background:linear-gradient(135deg,#2563eb,#1d4ed8);">
                                        <i class="fas fa-file-invoice-dollar fa-2x text-white"></i>
                                    </div>
                                </div>

                                <h5 class="font-weight-bold mb-1">Facturación Electrónica</h5>
                                <small class="text-muted d-block mb-3">SUNAT / APISPERU</small>

                                <hr class="my-3">

                                <div class="text-left">
                                    <div class="mb-3">
                                        <div class="side-title">Fecha Emisión</div>
                                        <div class="side-value" id="invoice_issue_date">{{ date('d/m/Y') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="side-title">Estado SUNAT</div>
                                        <span class="badge badge-warning px-3 py-2">Pendiente</span>
                                    </div>

                                    <div class="mb-3">
                                        <div class="side-title">Ambiente</div>
                                        <div class="side-value text-primary">BETA</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="side-title">Concepto</div>
                                        <div class="side-value" id="invoice_concept_preview">—</div>
                                    </div>

                                    <div class="mb-2">
                                        <div class="side-title">Monto Total</div>
                                        <div class="amount-main" id="invoice_total_preview">S/ 0.00</div>
                                    </div>
                                </div>
                            </div>

                            <div class="side-box">
                                <div class="font-weight-bold mb-2">Se generará:</div>
                                <ul class="list-check">
                                    <li>XML SUNAT</li>
                                    <li>PDF</li>
                                    <li>HASH</li>
                                    <li>CDR</li>
                                    <li>Respuesta SUNAT</li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <!-- PANEL DERECHO -->
                    <div class="col-lg-8">
                        <form id="invoiceForm">

                            @csrf

                            <input type="hidden" name="payment_id" id="invoice_payment_id">
                            <input type="hidden" name="sale_id" id="invoice_sale_id">

                            <input type="hidden" id="subtotal" name="subtotal">
                            <input type="hidden" id="tax_amount" name="tax_amount">
                            <input type="hidden" id="total_amount" name="total_amount">
                            <input type="hidden" id="customer_department" name="customer_department">
                            <input type="hidden" id="customer_province" name="customer_province">
                            <input type="hidden" id="customer_district" name="customer_district">
                            <input type="hidden" id="customer_ubigeo" name="customer_ubigeo">

                            <!-- EMPRESA -->
                            <div class="card invoice-card">
                                <div class="card-header">Empresa Emisora</div>
                                <div class="card-body">

                                    <input type="hidden" id="company_id" name="company_id">

                                    <input type="text" class="form-control" id="company_name" readonly>

                                </div>
                            </div>

                            <!-- COMPROBANTE -->
                            <div class="card invoice-card">
                                <div class="card-header">Información del Comprobante</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label>Tipo Documento</label>
                                            <select class="form-control" id="document_type" name="document_type">
                                                <option value="receipt">Boleta</option>
                                                <option value="invoice">Factura</option>
                                                <option value="sale_note">Nota de Venta</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <label>Serie</label>
                                            <input type="text" class="form-control" id="series" name="series"
                                                readonly>
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <label>Número</label>
                                            <input type="text" class="form-control" id="number" name="number"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CLIENTE -->
                            <div class="card invoice-card">
                                <div class="card-header">Datos del Cliente</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label>DNI / RUC</label>
                                            <input type="text" class="form-control" id="customer_document"
                                                name="customer_document">
                                        </div>

                                        <div class="col-md-8 mb-2">
                                            <label>Cliente</label>
                                            <input type="text" class="form-control" id="customer_name"
                                                name="customer_name">
                                        </div>
                                    </div>

                                    <div class="mt-1">
                                        <label>Dirección</label>
                                        <input type="text" class="form-control" id="customer_address"
                                            name="customer_address">
                                    </div>
                                </div>
                            </div>

                            <!-- DESCRIPCIÓN -->
                            <div class="card invoice-card">
                                <div class="card-header">Descripción del Comprobante</div>
                                <div class="card-body">
                                    <textarea class="form-control" rows="2" id="description" name="description"></textarea>

                                    <div class="mt-3">
                                        <label>Leyenda SUNAT</label>
                                        <textarea class="form-control" rows="2" id="legend" name="legend"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- RESUMEN -->
                            <div class="card invoice-card mb-0">
                                <div class="card-header bg-success text-white">Resumen Financiero</div>
                                <div class="card-body">
                                    <div class="summary-box">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="summary-label">Subtotal</div>
                                                <div class="summary-value" id="subtotal_preview">S/ 0.00</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="summary-label">IGV</div>
                                                <div class="summary-value" id="igv_preview">S/ 0.00</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="summary-label">Total</div>
                                                <div class="summary-value total" id="total_preview">S/ 0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>

                <button type="button" id="btnGenerateInvoice" class="btn btn-primary px-4">
                    <i class="fas fa-paper-plane mr-2"></i> Emitir Comprobante SUNAT
                </button>
            </div>
        </div>
    </div>
</div>
