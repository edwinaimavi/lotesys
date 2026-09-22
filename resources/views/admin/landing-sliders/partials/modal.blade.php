<div class="modal fade" id="landingSliderModal" tabindex="-1" role="dialog" aria-labelledby="landingSliderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header slider-modal-header">
                <div><h5 class="modal-title font-weight-bold" id="landingSliderModalLabel">Nuevo slide</h5><small class="text-muted">Configura el contenido que aparecerá en el hero público.</small></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="landingSliderForm" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" id="sliderId">
                <div class="modal-body bg-light p-3 p-md-4">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-section"><h6><i class="fas fa-align-left"></i> Contenido</h6>
                                <div class="form-group"><label for="sliderEyebrow">EYEBROW</label><input class="form-control form-control-sm preview-input" id="sliderEyebrow" name="eyebrow" maxlength="150"><div class="invalid-feedback" data-error="eyebrow"></div></div>
                                <div class="form-group"><label for="sliderTitle">TÍTULO <span class="text-danger">*</span></label><input class="form-control preview-input" id="sliderTitle" name="title" maxlength="220" required><div class="invalid-feedback" data-error="title"></div></div>
                                <div class="form-group"><label for="sliderDescription">DESCRIPCIÓN</label><textarea class="form-control form-control-sm preview-input" id="sliderDescription" name="description" rows="3" maxlength="1000"></textarea><div class="invalid-feedback" data-error="description"></div></div>
                                <div class="form-group mb-0"><label for="sliderSecondaryText">TEXTO SECUNDARIO</label><input class="form-control form-control-sm" id="sliderSecondaryText" name="secondary_text" maxlength="200"><div class="invalid-feedback" data-error="secondary_text"></div></div>
                            </div>
                            <div class="form-section"><h6><i class="fas fa-images"></i> Imágenes</h6>
                                <div class="form-row">
                                    <div class="form-group col-md-6 upload-field" data-upload-field="main">
                                        <div class="upload-heading">IMAGEN PRINCIPAL <span class="text-danger create-required">*</span></div>
                                        <input type="file" class="file-input-native" id="sliderImage" name="image" accept=".jpg,.jpeg,.png,.webp" tabindex="-1">
                                        <label class="upload-dropzone" for="sliderImage" role="button" tabindex="0">
                                            <i class="fas fa-cloud-upload-alt upload-icon" aria-hidden="true"></i>
                                            <strong>Arrastra una imagen aquí</strong><span>o</span><span class="upload-select-button">Seleccionar imagen</span>
                                            <small>Panorámica recomendada · JPG, PNG o WEBP · máximo 5 MB.</small>
                                        </label>
                                        <div class="existing-file" data-upload-existing hidden>
                                            <span class="existing-file-label">IMAGEN ACTUAL</span><img data-upload-existing-image src="" alt="Imagen principal actual">
                                            <button type="button" class="upload-action" data-upload-change><i class="fas fa-sync-alt mr-1"></i>Cambiar imagen</button>
                                        </div>
                                        <div class="selected-file" data-upload-selected hidden>
                                            <img data-upload-thumbnail src="" alt="Vista previa del archivo seleccionado"><div class="selected-file-info"><strong data-upload-name></strong><small data-upload-size></small></div>
                                            <button type="button" class="upload-action" data-upload-change>Cambiar</button><button type="button" class="upload-remove" data-upload-remove aria-label="Quitar archivo seleccionado"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback" data-error="image"></div>
                                    </div>
                                    <div class="form-group col-md-6 upload-field" data-upload-field="mobile">
                                        <div class="upload-heading">IMAGEN MÓVIL OPCIONAL</div>
                                        <input type="file" class="file-input-native" id="sliderMobileImage" name="mobile_image" accept=".jpg,.jpeg,.png,.webp" tabindex="-1">
                                        <label class="upload-dropzone" for="sliderMobileImage" role="button" tabindex="0">
                                            <i class="fas fa-mobile-alt upload-icon" aria-hidden="true"></i>
                                            <strong>Arrastra una imagen vertical</strong><span>o</span><span class="upload-select-button">Seleccionar imagen</span>
                                            <small>Recomendada para celulares. Si se omite, se utilizará la imagen principal.</small>
                                        </label>
                                        <div class="existing-file" data-upload-existing hidden>
                                            <span class="existing-file-label">IMAGEN MÓVIL ACTUAL</span><img data-upload-existing-image src="" alt="Imagen móvil actual">
                                            <button type="button" class="upload-action" data-upload-change><i class="fas fa-sync-alt mr-1"></i>Cambiar imagen</button>
                                        </div>
                                        <div class="selected-file" data-upload-selected hidden>
                                            <img data-upload-thumbnail src="" alt="Vista previa del archivo seleccionado"><div class="selected-file-info"><strong data-upload-name></strong><small data-upload-size></small></div>
                                            <button type="button" class="upload-action" data-upload-change>Cambiar</button><button type="button" class="upload-remove" data-upload-remove aria-label="Quitar archivo seleccionado"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="invalid-feedback" data-error="mobile_image"></div>
                                    </div>
                                </div>
                                <div class="form-group mb-0"><label for="sliderImageAlt">TEXTO ALTERNATIVO</label><input class="form-control form-control-sm" id="sliderImageAlt" name="image_alt" maxlength="180"><div class="invalid-feedback" data-error="image_alt"></div></div>
                            </div>
                            <div class="form-section"><h6><i class="fas fa-mouse-pointer"></i> Botón y publicación</h6>
                                <div class="form-row"><div class="form-group col-md-6"><label for="sliderButtonText">TEXTO DEL BOTÓN</label><input class="form-control form-control-sm preview-input" id="sliderButtonText" name="button_text" maxlength="80"><div class="invalid-feedback" data-error="button_text"></div></div><div class="form-group col-md-6"><label for="sliderButtonUrl">ENLACE</label><input class="form-control form-control-sm" id="sliderButtonUrl" name="button_url" maxlength="255" placeholder="#proyectos o https://..."><div class="invalid-feedback" data-error="button_url"></div></div></div>
                                <div class="form-row"><div class="form-group col-6 mb-0"><label for="sliderSortOrder">ORDEN <span class="text-danger">*</span></label><input type="number" min="1" class="form-control form-control-sm" id="sliderSortOrder" name="sort_order" value="1" required><div class="invalid-feedback" data-error="sort_order"></div></div><div class="form-group col-6 mb-0"><label for="sliderStatus">ESTADO</label><select class="form-control form-control-sm" id="sliderStatus" name="is_active"><option value="1">Activo</option><option value="0">Inactivo</option></select><div class="invalid-feedback" data-error="is_active"></div></div></div>
                            </div>
                        </div>
                        <div class="col-lg-5"><div class="preview-sticky"><span class="preview-label">PREVISUALIZACIÓN</span><div class="slider-preview" id="sliderPreview"><img id="previewImage" src="" alt=""><div class="slider-preview-overlay"></div><div class="slider-preview-copy"><small id="previewEyebrow">EYEBROW DEL SLIDE</small><h3 id="previewTitle">Título del slide</h3><p id="previewDescription">Descripción comercial del slide.</p><span id="previewButton">Texto del botón</span></div></div><small class="text-muted d-block mt-2">Vista aproximada. El hero público conserva su diseño responsive aprobado.</small></div></div>
                    </div>
                </div>
                <div class="modal-footer bg-white"><button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-krea" id="saveLandingSlider"><i class="fas fa-save mr-1"></i><span data-save-label>Guardar slide</span></button></div>
            </form>
        </div>
    </div>
</div>
