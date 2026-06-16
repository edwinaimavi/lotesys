<!-- MODAL GENERAR LOTES -->
<div class="modal fade"
     id="generateLotsModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="generateLotsModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered"
         role="document">

        <div class="modal-content border-0 shadow-lg rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header bg-gradient-success text-white">

                <h5 class="modal-title font-weight-bold"
                    id="generateLotsModalLabel">

                    <i class="fas fa-layer-group mr-2"></i>
                    Generación Masiva de Lotes

                </h5>

                <button type="button"
                        class="close text-white"
                        data-dismiss="modal"
                        aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body bg-light">

                <form id="generateLotsForm">

                    @csrf

                    <input type="hidden"
                           id="gl_project_id"
                           name="project_id">

                    <input type="hidden"
                           id="gl_block_id"
                           name="block_id">

                    <div class="row">

                        <!-- LEFT -->
                        <div class="col-md-4 border-right text-center">

                            <div class="mb-4">

                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto shadow"
                                     style="
                                        width:150px;
                                        height:150px;
                                        background: linear-gradient(135deg,#28a745,#1e7e34);
                                        color:white;
                                        font-size:60px;
                                     ">

                                    <i class="fas fa-map-marked-alt"></i>

                                </div>

                            </div>

                            <h4 id="gl_block_name"
                                class="font-weight-bold text-dark">

                                MZ A

                            </h4>

                            <p id="gl_project_name"
                               class="text-muted mb-4">

                                Residencial Las Palmeras

                            </p>

                            <div class="alert alert-warning text-left shadow-sm">

                                <small>

                                    <i class="fas fa-info-circle mr-1"></i>

                                    Los lotes serán creados automáticamente con estado:

                                    <strong>BLOQUEADO</strong>

                                </small>

                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="col-md-8">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body">

                                    <h5 class="font-weight-bold text-success mb-4">

                                        <i class="fas fa-cogs mr-2"></i>
                                        Configuración de Lotes

                                    </h5>

                                    <div class="row">

                                        <!-- CANTIDAD -->
                                        <div class="col-md-6">

                                            <div class="form-group">

                                                <label class="font-weight-bold">
                                                    Cantidad de Lotes <span class="text-danger">*</span>
                                                </label>

                                                <input type="number"
                                                       class="form-control"
                                                       id="quantity"
                                                       name="quantity"
                                                       min="1"
                                                       placeholder="Ej: 10"
                                                       required>

                                            </div>

                                        </div>

                                        <!-- ÁREA -->
                                        <div class="col-md-6">

                                            <div class="form-group">

                                                <label class="font-weight-bold">
                                                    Área <span class="text-danger">*</span>
                                                </label>

                                                <input type="number"
                                                       step="0.01"
                                                       class="form-control"
                                                       id="area"
                                                       name="area"
                                                       placeholder="Ej: 120"
                                                       required>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="row">

                                        <!-- UNIDAD -->
                                        <div class="col-md-6">

                                            <div class="form-group">

                                                <label class="font-weight-bold">
                                                    Unidad de Medida <span class="text-danger">*</span>
                                                </label>

                                                <select class="form-control"
                                                        id="unit_measure"
                                                        name="unit_measure"
                                                        required>

                                                    <option value="m2">m²</option>

                                                    <option value="ha">ha</option>

                                                   
                                                </select>

                                            </div>

                                        </div>

                                        <!-- INFO -->
                                        <div class="col-md-6">

                                            <div class="form-group">

                                                <label class="font-weight-bold">
                                                    Estado Inicial
                                                </label>

                                                <input type="text"
                                                       class="form-control bg-dark text-white font-weight-bold"
                                                       value="BLOQUEADO"
                                                       readonly>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="alert alert-info mt-3 mb-0">

                                        <small>

                                            <i class="fas fa-lightbulb mr-1"></i>

                                            El sistema generará automáticamente:

                                            <strong>
                                                códigos únicos,
                                                números de lote,
                                                y evitará duplicados.
                                            </strong>

                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer bg-white">

                <button type="button"
                        class="btn btn-secondary px-4"
                        data-dismiss="modal">

                    <i class="fas fa-times mr-1"></i>
                    Cancelar

                </button>

                <button type="submit"
                        form="generateLotsForm"
                        class="btn btn-success px-4 shadow-sm">

                    <i class="fas fa-layer-group mr-1"></i>
                    Generar Lotes

                </button>

            </div>

        </div>

    </div>

</div>