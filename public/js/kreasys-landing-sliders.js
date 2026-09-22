(() => {
    'use strict';

    const module = document.querySelector('#landingSliderModule');
    if (!module) return;

    const form = document.querySelector('#landingSliderForm');
    const modal = window.jQuery('#landingSliderModal');
    const viewModal = window.jQuery('#landingSliderViewModal');
    const tableBody = document.querySelector('#landingSliderTableBody');
    const token = form.querySelector('[name="_token"]').value;
    const baseUrl = module.dataset.baseUrl;
    const uploadFields = [...form.querySelectorAll('[data-upload-field]')];
    const uploadObjectUrls = new Map();
    const permissions = {
        view: module.dataset.canView === '1',
        edit: module.dataset.canEdit === '1',
        toggle: module.dataset.canToggle === '1',
    };
    let isSubmitting = false;

    class RequestError extends Error {
        constructor(status, message, errors = {}) {
            super(message);
            this.status = status;
            this.errors = errors;
        }
    }

    const field = id => document.querySelector(`#${id}`);
    const value = (id, fallback = '') => field(id).value.trim() || fallback;
    const setText = (id, valueToSet) => { field(id).textContent = valueToSet || '—'; };
    const clearFieldError = input => {
        if (!input?.name) return;
        input.classList.remove('is-invalid');
        input.closest('[data-upload-field]')?.classList.remove('has-error');
        const feedback = [...form.querySelectorAll('[data-error]')].find(element => element.dataset.error === input.name);
        if (feedback) feedback.textContent = '';
    };
    const clearErrors = () => {
        form.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
        form.querySelectorAll('.has-error').forEach(element => element.classList.remove('has-error'));
        form.querySelectorAll('[data-error]').forEach(element => { element.textContent = ''; });
    };
    const showErrors = errors => {
        let firstInvalid = null;
        Object.entries(errors).forEach(([name, messages]) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                input.classList.add('is-invalid');
                firstInvalid ||= input;
            }
            input?.closest('[data-upload-field]')?.classList.add('has-error');
            const feedback = [...form.querySelectorAll('[data-error]')].find(element => element.dataset.error === name);
            if (feedback) feedback.textContent = messages[0];
        });
        const upload = firstInvalid?.closest('[data-upload-field]')?.querySelector('[data-upload-selected]:not([hidden]) [data-upload-change], [data-upload-existing]:not([hidden]) [data-upload-change], .upload-dropzone:not([hidden])');
        (upload || firstInvalid)?.focus();
    };
    const showMessage = (icon, title, message) => {
        if (window.Swal) return window.Swal.fire({ icon, title, text: message, confirmButtonColor: '#87bc27' });
        module.querySelector('.slider-inline-notice')?.remove();
        const notice = document.createElement('div');
        notice.className = `alert alert-${icon === 'success' ? 'success' : 'danger'} slider-inline-notice`;
        notice.setAttribute('role', 'alert');
        const strong = document.createElement('strong');
        strong.textContent = `${title}. `;
        notice.append(strong, document.createTextNode(message || ''));
        module.prepend(notice);
        return Promise.resolve();
    };
    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const result = await response.json().catch(() => ({}));
        if (response.ok) return result;

        let message = result.message || 'No se pudo completar la operación.';
        if (response.status === 403) message = 'No tienes permisos para realizar esta acción.';
        if (response.status === 419) message = 'Tu sesión ha expirado. Actualiza la página e inténtalo nuevamente.';
        if (response.status >= 500) message = 'No se pudo completar la operación. Inténtalo nuevamente.';
        throw new RequestError(response.status, message, result.errors || {});
    };
    const updatePreview = () => {
        setText('previewEyebrow', value('sliderEyebrow', 'EYEBROW DEL SLIDE'));
        setText('previewTitle', value('sliderTitle', 'Título del slide'));
        setText('previewDescription', value('sliderDescription', 'Descripción comercial del slide.'));
        setText('previewButton', value('sliderButtonText', 'Texto del botón'));
    };
    const setPreviewImage = url => {
        const image = field('previewImage');
        image.src = url || '';
        image.hidden = !url;
    };
    const releaseUploadUrl = component => {
        const objectUrl = uploadObjectUrls.get(component);
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        uploadObjectUrls.delete(component);
    };
    const formatFileSize = bytes => {
        if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };
    const restoreUploadDisplay = component => {
        const hasExisting = Boolean(component.dataset.existingUrl);
        component.querySelector('[data-upload-selected]').hidden = true;
        component.querySelector('[data-upload-existing]').hidden = !hasExisting;
        component.querySelector('.upload-dropzone').hidden = hasExisting;
    };
    const setExistingUpload = (type, url = '') => {
        const component = form.querySelector(`[data-upload-field="${type}"]`);
        component.dataset.existingUrl = url || '';
        component.querySelector('[data-upload-existing-image]').src = url || '';
        restoreUploadDisplay(component);
    };
    const showSelectedFile = (component, file) => {
        releaseUploadUrl(component);
        const objectUrl = URL.createObjectURL(file);
        uploadObjectUrls.set(component, objectUrl);
        component.querySelector('[data-upload-thumbnail]').src = objectUrl;
        component.querySelector('[data-upload-name]').textContent = file.name;
        component.querySelector('[data-upload-size]').textContent = formatFileSize(file.size);
        component.querySelector('[data-upload-selected]').hidden = false;
        component.querySelector('[data-upload-existing]').hidden = true;
        component.querySelector('.upload-dropzone').hidden = true;
        clearFieldError(component.querySelector('input[type="file"]'));
        if (component.dataset.uploadField === 'main') setPreviewImage(objectUrl);
    };
    const clearSelectedFile = component => {
        const input = component.querySelector('input[type="file"]');
        input.value = '';
        clearFieldError(input);
        releaseUploadUrl(component);
        restoreUploadDisplay(component);
        if (component.dataset.uploadField === 'main') setPreviewImage(component.dataset.existingUrl || '');
    };
    const resetUploads = () => {
        uploadFields.forEach(component => {
            component.querySelector('input[type="file"]').value = '';
            releaseUploadUrl(component);
            setExistingUpload(component.dataset.uploadField, '');
            component.classList.remove('is-dragging', 'has-error');
        });
    };
    const setSaveState = (busy, editing) => {
        const submit = field('saveLandingSlider');
        submit.disabled = busy;
        submit.querySelector('i').className = busy ? 'fas fa-spinner fa-spin mr-1' : 'fas fa-save mr-1';
        submit.querySelector('[data-save-label]').textContent = busy
            ? (editing ? 'Guardando cambios...' : 'Guardando...')
            : (editing ? 'Guardar cambios' : 'Guardar slide');
    };
    const fetchSlider = async id => {
        const result = await requestJson(`${baseUrl}/${id}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        return result.data;
    };
    const resetForm = () => {
        form.reset();
        clearErrors();
        resetUploads();
        field('sliderId').value = '';
        field('sliderSortOrder').value = '1';
        field('sliderStatus').value = '1';
        field('sliderImage').required = true;
        document.querySelector('.create-required').hidden = false;
        document.querySelector('#landingSliderModalLabel').textContent = 'Nuevo slide';
        setSaveState(false, false);
        setPreviewImage('');
        updatePreview();
    };
    const fillForm = data => {
        uploadFields.forEach(component => {
            component.querySelector('input[type="file"]').value = '';
            releaseUploadUrl(component);
        });
        field('sliderId').value = data.id;
        field('sliderEyebrow').value = data.eyebrow || '';
        field('sliderTitle').value = data.title || '';
        field('sliderDescription').value = data.description || '';
        field('sliderSecondaryText').value = data.secondary_text || '';
        field('sliderButtonText').value = data.button_text || '';
        field('sliderButtonUrl').value = data.button_url || '';
        field('sliderImageAlt').value = data.image_alt || '';
        field('sliderSortOrder').value = data.sort_order;
        field('sliderStatus').value = data.is_active ? '1' : '0';
        field('sliderImage').required = false;
        document.querySelector('.create-required').hidden = true;
        document.querySelector('#landingSliderModalLabel').textContent = 'Editar slide';
        setSaveState(false, true);
        setExistingUpload('main', data.image_url);
        setExistingUpload('mobile', data.mobile_image_url || '');
        setPreviewImage(data.image_url);
        updatePreview();
    };

    const makeIconButton = (className, id, title, icon) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn ${className}`;
        button.dataset.id = id;
        button.title = title;
        const element = document.createElement('i');
        element.className = `fas ${icon}`;
        button.append(element);
        return button;
    };
    const buildRow = data => {
        const row = document.createElement('tr');
        row.dataset.sliderId = data.id;
        row.dataset.sortOrder = data.sort_order;
        row.dataset.active = data.is_active ? '1' : '0';

        const position = document.createElement('td');
        const imageCell = document.createElement('td');
        const image = document.createElement('img');
        image.className = 'slider-thumb';
        image.src = data.image_url;
        image.alt = data.image_alt || 'Miniatura del slide';
        imageCell.append(image);

        const titleCell = document.createElement('td');
        titleCell.className = 'text-left';
        const title = document.createElement('strong');
        title.className = 'd-block text-dark';
        title.textContent = data.title;
        const eyebrow = document.createElement('small');
        eyebrow.className = 'text-muted';
        eyebrow.textContent = data.eyebrow || 'Sin eyebrow';
        titleCell.append(title, eyebrow);

        const orderCell = document.createElement('td');
        const order = document.createElement('span');
        order.className = 'order-pill';
        order.textContent = data.sort_order;
        orderCell.append(order);

        const statusCell = document.createElement('td');
        const status = document.createElement('span');
        status.className = `badge badge-${data.is_active ? 'success' : 'secondary'} px-3 py-2`;
        status.textContent = data.is_active ? 'ACTIVO' : 'INACTIVO';
        statusCell.append(status);

        const actionsCell = document.createElement('td');
        const actions = document.createElement('div');
        actions.className = 'btn-group btn-group-sm';
        actions.setAttribute('role', 'group');
        actions.setAttribute('aria-label', 'Acciones del slide');
        if (permissions.view) actions.append(makeIconButton('btn-outline-info view-slider', data.id, 'Ver', 'fa-eye'));
        if (permissions.edit) actions.append(makeIconButton('btn-outline-primary edit-slider', data.id, 'Editar', 'fa-pen'));
        if (permissions.toggle) {
            const toggle = makeIconButton(`btn-outline-${data.is_active ? 'warning' : 'success'} toggle-slider`, data.id, data.is_active ? 'Desactivar' : 'Activar', data.is_active ? 'fa-pause' : 'fa-play');
            toggle.dataset.active = data.is_active ? '1' : '0';
            actions.append(toggle);
        }
        actionsCell.append(actions);
        row.append(position, imageCell, titleCell, orderCell, statusCell, actionsCell);
        return row;
    };
    const ensureEmptyState = () => {
        if (tableBody.querySelector('[data-slider-id]')) {
            tableBody.querySelector('[data-empty-state]')?.remove();
            return;
        }
        if (tableBody.querySelector('[data-empty-state]')) return;
        const row = document.createElement('tr');
        row.dataset.emptyState = '';
        const cell = document.createElement('td');
        cell.colSpan = 6;
        cell.className = 'py-5 text-muted';
        cell.textContent = 'No hay slides registrados.';
        row.append(cell);
        tableBody.append(row);
    };
    const sortAndRenumberRows = () => {
        const rows = [...tableBody.querySelectorAll('[data-slider-id]')];
        rows.sort((left, right) => Number(left.dataset.sortOrder) - Number(right.dataset.sortOrder) || Number(left.dataset.sliderId) - Number(right.dataset.sliderId));
        rows.forEach((row, index) => {
            row.firstElementChild.textContent = index + 1;
            tableBody.append(row);
        });
        ensureEmptyState();
    };
    const updateCounters = () => {
        const rows = [...tableBody.querySelectorAll('[data-slider-id]')];
        const active = rows.filter(row => row.dataset.active === '1').length;
        module.querySelector('[data-slider-total]').textContent = rows.length;
        module.querySelector('[data-slider-active]').textContent = active;
        module.querySelector('[data-warning-active-count]').textContent = active;
        field('activeSlidesWarning').hidden = active <= 5;
    };
    const upsertRow = data => {
        const current = tableBody.querySelector(`[data-slider-id="${data.id}"]`);
        const row = buildRow(data);
        if (current) current.replaceWith(row);
        else tableBody.append(row);
        sortAndRenumberRows();
        updateCounters();
    };
    const updateToggleRow = (id, active) => {
        const row = tableBody.querySelector(`[data-slider-id="${id}"]`);
        if (!row) return;
        row.dataset.active = active ? '1' : '0';
        const status = row.children[4].querySelector('.badge');
        status.className = `badge badge-${active ? 'success' : 'secondary'} px-3 py-2`;
        status.textContent = active ? 'ACTIVO' : 'INACTIVO';
        const button = row.querySelector('.toggle-slider');
        if (button) {
            button.dataset.active = active ? '1' : '0';
            button.title = active ? 'Desactivar' : 'Activar';
            button.className = `btn btn-outline-${active ? 'warning' : 'success'} toggle-slider`;
            button.querySelector('i').className = `fas ${active ? 'fa-pause' : 'fa-play'}`;
        }
        updateCounters();
    };

    document.querySelector('#newLandingSlider')?.addEventListener('click', () => {
        resetForm();
        modal.modal('show');
    });
    form.querySelectorAll('.preview-input').forEach(input => input.addEventListener('input', updatePreview));
    ['input', 'change'].forEach(type => form.addEventListener(type, event => clearFieldError(event.target)));
    uploadFields.forEach(component => {
        const input = component.querySelector('input[type="file"]');
        const dropzone = component.querySelector('.upload-dropzone');
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) showSelectedFile(component, file);
        });
        dropzone.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });
        component.querySelectorAll('[data-upload-change]').forEach(button => button.addEventListener('click', () => input.click()));
        component.querySelector('[data-upload-remove]').addEventListener('click', () => clearSelectedFile(component));
        ['dragenter', 'dragover'].forEach(type => component.addEventListener(type, event => {
            event.preventDefault();
            event.stopPropagation();
            component.classList.add('is-dragging');
        }));
        ['dragleave', 'drop'].forEach(type => component.addEventListener(type, event => {
            event.preventDefault();
            event.stopPropagation();
            component.classList.remove('is-dragging');
        }));
        component.addEventListener('drop', event => {
            const file = event.dataTransfer.files[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    modal.on('hidden.bs.modal', resetForm);

    module.addEventListener('click', async event => {
        const editButton = event.target.closest('.edit-slider');
        const viewButton = event.target.closest('.view-slider');
        const toggleButton = event.target.closest('.toggle-slider');
        try {
            if (editButton) {
                clearErrors();
                fillForm(await fetchSlider(editButton.dataset.id));
                modal.modal('show');
            }
            if (viewButton) {
                const data = await fetchSlider(viewButton.dataset.id);
                field('viewSliderImage').src = data.image_url;
                field('viewSliderImage').alt = data.image_alt || '';
                setText('viewSliderEyebrow', data.eyebrow);
                setText('viewSliderTitle', data.title);
                setText('viewSliderDescription', data.description);
                setText('viewSliderSecondary', data.secondary_text);
                setText('viewSliderButton', data.button_text);
                setText('viewSliderUrl', data.button_url);
                setText('viewSliderOrder', String(data.sort_order));
                setText('viewSliderStatus', data.is_active ? 'Activo' : 'Inactivo');
                viewModal.modal('show');
            }
            if (toggleButton) {
                const activating = toggleButton.dataset.active !== '1';
                const confirmed = window.Swal
                    ? (await window.Swal.fire({ icon: 'question', title: activating ? '¿Activar slide?' : '¿Desactivar slide?', text: activating ? 'Volverá a aparecer en la web pública.' : 'Dejará de aparecer en la web pública.', showCancelButton: true, confirmButtonText: activating ? 'Sí, activar' : 'Sí, desactivar', cancelButtonText: 'Cancelar', confirmButtonColor: '#87bc27' })).isConfirmed
                    : window.confirm(activating ? '¿Activar slide?' : '¿Desactivar slide?');
                if (!confirmed) return;
                toggleButton.disabled = true;
                toggleButton.querySelector('i').className = 'fas fa-spinner fa-spin';
                try {
                    const result = await requestJson(`${baseUrl}/${toggleButton.dataset.id}/toggle`, {
                        method: 'PATCH',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    updateToggleRow(result.data.id, result.data.is_active);
                    await showMessage('success', 'Estado actualizado', result.message);
                } finally {
                    toggleButton.disabled = false;
                    const active = toggleButton.dataset.active === '1';
                    toggleButton.className = `btn btn-outline-${active ? 'warning' : 'success'} toggle-slider`;
                    toggleButton.title = active ? 'Desactivar' : 'Activar';
                    toggleButton.querySelector('i').className = `fas ${active ? 'fa-pause' : 'fa-play'}`;
                }
            }
        } catch (error) {
            const message = error instanceof RequestError ? error.message : 'No se pudo completar la operación. Inténtalo nuevamente.';
            await showMessage('error', 'Ocurrió un problema', message);
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (isSubmitting) return;
        clearErrors();
        const id = field('sliderId').value;
        const editing = Boolean(id);
        const data = new FormData(form);
        if (editing) data.append('_method', 'PUT');
        isSubmitting = true;
        setSaveState(true, editing);

        try {
            const result = await requestJson(editing ? `${baseUrl}/${id}` : module.dataset.storeUrl, {
                method: 'POST',
                body: data,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            upsertRow(result.data);
            modal.modal('hide');
            await showMessage('success', editing ? 'Slide actualizado' : 'Slide guardado', result.message);
        } catch (error) {
            if (error.status === 422) showErrors(error.errors);
            else {
                const message = error instanceof RequestError ? error.message : 'No se pudo completar la operación. Inténtalo nuevamente.';
                await showMessage('error', 'Ocurrió un problema', message);
            }
        } finally {
            isSubmitting = false;
            setSaveState(false, Boolean(field('sliderId').value));
        }
    });
})();
