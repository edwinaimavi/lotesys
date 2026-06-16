<!-- MODAL LOTE -->
<div class="modal fade" id="lotModal" tabindex="-1" role="dialog" aria-labelledby="lotModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="background: linear-gradient(90deg,#ffffff,#f3f6f8); border-bottom:1px solid #e6eaee;">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-map text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="lotModalLabel">
                            Nuevo Lote
                        </h5>

                        <small class="text-muted">
                            Registro de lotes inmobiliarios
                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="lotForm" autocomplete="off" class="row">

                    @csrf

                    <!-- PANEL IZQUIERDO -->
                    <div class="col-lg-4 mb-3">

                        <div class="card border-0 rounded-lg shadow-sm h-100">

                            <div class="card-body text-center py-4">

                                <div class="mb-3">

                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="
                                            width:120px;
                                            height:120px;
                                            background:linear-gradient(135deg,#007bff,#0056b3);
                                            color:white;
                                            font-size:45px;
                                            box-shadow:0 6px 18px rgba(0,0,0,.1);
                                         ">

                                        <i class="fas fa-map"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">
                                    Lote
                                </h5>

                                <small class="text-muted">
                                    Gestión de lotes inmobiliarios
                                </small>

                                <hr>

                                <div class="text-left">

                                    <small class="text-muted">
                                        Fecha de registro
                                    </small>

                                    <div class="font-weight-600">
                                        {{ now()->format('d/m/Y') }}
                                    </div>

                                    <small class="text-muted d-block mt-3">
                                        Estado inicial
                                    </small>

                                    <div class="badge badge-success py-2 px-3 mt-1">
                                        Disponible
                                    </div>

                                    <small class="text-muted d-block mt-3">
                                        Módulo
                                    </small>

                                    <div class="font-weight-600">
                                        Lotes
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PANEL DERECHO -->
                    <div class="col-lg-8">

                        <div class="card border-0 rounded-lg shadow-sm">

                            <div class="card-body">

                                <!-- FILA 1 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="project_id" class="small font-weight-bold text-secondary">

                                            PROYECTO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="project_id" name="project_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione un proyecto
                                            </option>

                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach

                                        </select>

                                        <span class="invalid-feedback" id="project_id-error"></span>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="block_id" class="small font-weight-bold text-secondary">

                                            MANZANA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="block_id" name="block_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione una manzana
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="block_id-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="code" class="small font-weight-bold text-secondary">

                                            CÓDIGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" class="form-control form-control-sm" id="code"
                                            name="code" placeholder="Ej: MZA-L01" readonly>

                                        <span class="invalid-feedback" id="code-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="number" class="small font-weight-bold text-secondary">

                                            NÚMERO LOTE
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" class="form-control form-control-sm" id="number"
                                            name="number" placeholder="Ej: 01">

                                        <span class="invalid-feedback" id="number-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="area" class="small font-weight-bold text-secondary">

                                            ÁREA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" class="form-control form-control-sm"
                                            id="area" name="area" placeholder="0.00">

                                        <span class="invalid-feedback" id="area-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="unit_measure" class="small font-weight-bold text-secondary">

                                            UNIDAD

                                        </label>

                                        <select id="unit_measure" name="unit_measure"
                                            class="form-control form-control-sm">

                                            <option value="m2">
                                                m²
                                            </option>

                                            <option value="ha">
                                                Hectárea
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="unit_measure-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="cash_price" class="small font-weight-bold text-secondary">

                                            PRECIO CONTADO

                                        </label>

                                        <input type="number" step="0.01" class="form-control form-control-sm"
                                            id="cash_price" name="cash_price" placeholder="0.00">

                                        <span class="invalid-feedback" id="cash_price-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="financed_price" class="small font-weight-bold text-secondary">

                                            PRECIO FINANCIADO

                                        </label>

                                        <input type="number" step="0.01" class="form-control form-control-sm"
                                            id="financed_price" name="financed_price" placeholder="0.00">

                                        <span class="invalid-feedback" id="financed_price-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 4 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="north_boundary" class="small font-weight-bold text-secondary">

                                            COLINDANTE NORTE

                                        </label>

                                        <input type="text" class="form-control form-control-sm"
                                            id="north_boundary" name="north_boundary">

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="south_boundary" class="small font-weight-bold text-secondary">

                                            COLINDANTE SUR

                                        </label>

                                        <input type="text" class="form-control form-control-sm"
                                            id="south_boundary" name="south_boundary">

                                    </div>

                                </div>

                                <!-- FILA 5 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="east_boundary" class="small font-weight-bold text-secondary">

                                            COLINDANTE ESTE

                                        </label>

                                        <input type="text" class="form-control form-control-sm" id="east_boundary"
                                            name="east_boundary">

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="west_boundary" class="small font-weight-bold text-secondary">

                                            COLINDANTE OESTE

                                        </label>

                                        <input type="text" class="form-control form-control-sm" id="west_boundary"
                                            name="west_boundary">

                                    </div>

                                </div>

                                <!-- FILA 6 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="status" class="small font-weight-bold text-secondary">

                                            ESTADO

                                        </label>

                                        <select id="status" name="status" class="form-control form-control-sm">

                                            <option value="disponible" selected>
                                                Disponible
                                            </option>

                                            <option value="separado">
                                                Separado
                                            </option>

                                            <option value="vendido">
                                                Vendido
                                            </option>

                                            <option value="rescindido">
                                                Rescindido
                                            </option>

                                            <option value="bloqueado">
                                                Bloqueado
                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- FILA 7 -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="observation" class="small font-weight-bold text-secondary">

                                            OBSERVACIÓN

                                        </label>

                                        <textarea id="observation" name="observation" rows="4" class="form-control form-control-sm"
                                            placeholder="Observaciones adicionales"></textarea>

                                    </div>

                                </div>

                                <!-- NOTA -->
                                <div class="form-row">

                                    <div class="col-12">

                                        <small class="text-muted">

                                            La información registrada aquí será utilizada
                                            para ventas, contratos, pagos y cronogramas.

                                        </small>

                                    </div>

                                </div>

                                <!-- ACCIONES -->
                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button" class="btn btn-light border mr-2"
                                            data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-primary" id="btnSaveLot">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Lote

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
