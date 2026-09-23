<div class="modal fade" id="contractSaleModal" tabindex="-1" role="dialog" aria-labelledby="contractSaleTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <div>
                    <h5 id="contractSaleTitle" class="modal-title font-weight-bold"><i class="fas fa-file-word text-primary mr-2"></i>GENERAR CONTRATO</h5>
                    <div class="small mt-1">Venta: <strong id="contractSaleCode"></strong> · <span id="contractCustomerName"></span></div>
                    <div id="contractCompanyProject" class="small text-muted"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body bg-light">
                <p class="small text-muted">Los cambios se aplican únicamente a este Word. Los campos sin datos quedan vacíos. Los importes en letras y fechas derivadas se actualizan al generar, salvo que los edite manualmente.</p>
                <div id="contractWarning" class="alert alert-warning small" role="alert"></div>
                <form id="contractSaleForm" autocomplete="off">
                    @csrf
                    <div id="contractFields"></div>
                </form>
            </div>
            <div class="modal-footer bg-white" style="position:sticky;bottom:0;z-index:2">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Cerrar</button>
                <button type="submit" form="contractSaleForm" class="btn btn-primary" id="generateContractButton">
                    <i class="fas fa-file-word mr-1"></i> Generar Word
                </button>
            </div>
        </div>
    </div>
</div>
