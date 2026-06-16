<!-- ===================================================== -->
<!-- MODAL FACTURACIÓN ELECTRÓNICA -->
<!-- ===================================================== -->

<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog" aria-labelledby="invoiceModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- ========================================= -->
            <!-- HEADER -->
            <!-- ========================================= -->

            <div class="modal-header align-items-center"
                style="
                    background:
                    linear-gradient(
                        90deg,
                        #ffffff,
                        #f4f8ff
                    );
                    border-bottom:1px solid #e6eaee;
                ">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-file-invoice-dollar text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="invoiceModalLabel">

                            Generar Comprobante Electrónico

                        </h5>

                        <small class="text-muted">

                            Facturación electrónica SUNAT

                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- ========================================= -->
            <!-- BODY -->
            <!-- ========================================= -->

            <div class="modal-body p-3" style="background:#f8fbfc;">

                <form id="invoiceForm" autocomplete="off" class="row">

                    @csrf

                    <!-- ===================================== -->
                    <!-- PANEL IZQUIERDO -->
                    <!-- ===================================== -->

                    <div class="col-lg-4 mb-3">

                        <div class="card border-0 rounded-lg shadow-sm h-100">

                            <div class="card-body text-center py-4">

                                <div class="mb-3">

                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="
                                            width:120px;
                                            height:120px;
                                            background:
                                            linear-gradient(
                                                135deg,
                                                #007bff,
                                                #0056b3
                                            );
                                            color:white;
                                            font-size:45px;
                                        ">

                                        <i class="fas fa-file-invoice-dollar"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Facturación Electrónica

                                </h5>

                                <small class="text-muted">

                                    SUNAT / APISPERU

                                </small>

                                <hr>

                                <div class="text-left">

                                    <small class="text-muted">

                                        Fecha Emisión

                                    </small>

                                    <div class="font-weight-600">

                                        {{ now()->format('d/m/Y') }}

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Estado SUNAT

                                    </small>

                                    <div class="badge badge-warning py-2 px-3 mt-1">

                                        Pendiente

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Ambiente

                                    </small>

                                    <div class="font-weight-600">

                                        Beta

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Proveedor

                                    </small>

                                    <div class="font-weight-600">

                                        APISPERU

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ===================================== -->
                    <!-- PANEL DERECHO -->
                    <!-- ===================================== -->

                    <div class="col-lg-8">

                        <div class="card border-0 rounded-lg shadow-sm">

                            <div class="card-body">

                                <!-- ================================= -->
                                <!-- FILA 1 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            TIPO DOCUMENTO

                                        </label>

                                        <select id="document_type" name="document_type"
                                            class="form-control form-control-sm">

                                            <option value="receipt">

                                                Boleta

                                            </option>

                                            <option value="invoice">

                                                Factura

                                            </option>

                                        </select>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            SERIE

                                        </label>

                                        <input type="text" id="series" name="series"
                                            class="form-control form-control-sm" readonly>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            NÚMERO

                                        </label>

                                        <input type="text" id="number" name="number"
                                            class="form-control form-control-sm" readonly>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- FILA 2 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label class="small font-weight-bold text-secondary">

                                            CLIENTE

                                        </label>

                                        <input type="text" id="customer_name" class="form-control form-control-sm"
                                            readonly>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label class="small font-weight-bold text-secondary">

                                            DOCUMENTO

                                        </label>

                                        <input type="text" id="customer_document"
                                            class="form-control form-control-sm" readonly>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- FILA 3 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            SUBTOTAL

                                        </label>

                                        <input type="text" id="subtotal" class="form-control form-control-sm"
                                            readonly>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            IGV

                                        </label>

                                        <input type="text" id="tax_amount" class="form-control form-control-sm"
                                            readonly>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            TOTAL

                                        </label>

                                        <input type="text" id="total_amount"
                                            class="form-control form-control-sm font-weight-bold" readonly>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- OBSERVACIÓN -->
                                <!-- ================================= -->

                                <div class="form-group">

                                    <label class="small font-weight-bold text-secondary">

                                        OBSERVACIÓN

                                    </label>

                                    <textarea id="observation" class="form-control" rows="3" readonly></textarea>

                                </div>

                                <!-- ================================= -->
                                <!-- ALERTA -->
                                <!-- ================================= -->

                                <div class="alert alert-primary border-0 shadow-sm mt-4">

                                    <div class="d-flex">

                                        <div class="mr-3">

                                            <i class="fas fa-info-circle fa-2x text-primary"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                Importante:
                                            </strong>

                                            <br>

                                            Al generar el comprobante se creará:

                                            <ul class="mb-0 mt-2">

                                                <li>XML SUNAT</li>

                                                <li>PDF</li>

                                                <li>HASH</li>

                                                <li>CDR SUNAT</li>

                                            </ul>

                                        </div>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- ACCIONES -->
                                <!-- ================================= -->

                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button" class="btn btn-light border mr-2"
                                            data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-primary" id="btnGenerateInvoice">

                                            <i class="fas fa-file-invoice-dollar mr-1"></i>
                                            Generar Comprobante

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
