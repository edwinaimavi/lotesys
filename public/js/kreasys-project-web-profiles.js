(() => {
    'use strict';

    const module = document.querySelector('#projectWebModule');
    if (!module || !window.jQuery) return;

    const form = document.querySelector('#projectWebForm');
    const modal = window.jQuery('#projectWebModal');
    const viewModal = window.jQuery('#projectWebViewModal');
    const baseUrl = module.dataset.baseUrl;
    const uploads = [...form.querySelectorAll('[data-upload]')];
    const objectUrls = new Map();
    let saving = false;

    const byId = id => document.getElementById(id);
    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const result = await response.json().catch(() => ({}));
        if (response.ok) return result;
        const error = new Error(response.status === 403 ? 'No tienes permisos para realizar esta acción.' : (result.message || 'No se pudo completar la operación.'));
        error.status = response.status;
        error.errors = result.errors || {};
        throw error;
    };
    const notify = (icon, title, message) => window.Swal
        ? window.Swal.fire({ icon, title, text: message, confirmButtonColor: '#87bc27' })
        : Promise.resolve(window.alert(`${title}: ${message}`));
    const clearErrors = () => {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
        form.querySelectorAll('[data-error]').forEach(el => { el.textContent = ''; });
    };
    const showErrors = errors => {
        Object.entries(errors).forEach(([name, messages]) => {
            const input = form.querySelector(`[name="${name}"]`);
            input?.classList.add('is-invalid');
            input?.closest('[data-upload]')?.classList.add('has-error');
            const feedback = form.querySelector(`[data-error="${name}"]`);
            if (feedback) feedback.textContent = messages[0];
        });
    };
    const releasePreview = upload => {
        const url = objectUrls.get(upload);
        if (url) URL.revokeObjectURL(url);
        objectUrls.delete(upload);
    };
    const setPreview = (upload, url) => {
        const preview = upload.querySelector('[data-preview]');
        const zone = upload.querySelector('.web-dropzone');
        preview.src = url || '';
        preview.hidden = !url;
        zone.classList.toggle('has-image', Boolean(url));
        if (upload.dataset.upload === 'cover') {
            const cardImage = byId('webCardPreviewImage');
            cardImage.src = url || '';
            cardImage.hidden = !url;
        }
    };
    const resetUploads = data => {
        uploads.forEach(upload => {
            releasePreview(upload);
            upload.classList.remove('is-dragging', 'has-error');
            upload.querySelector('input').value = '';
            const existing = ({
                cover: data.cover_image_url,
                mobile: data.mobile_image_url,
                plan: data.plan_image_url,
                plan_mobile: data.plan_mobile_image_url,
            })[upload.dataset.upload];
            upload.dataset.existingUrl = existing || '';
            upload.querySelector('[data-file-meta]').hidden = true;
            setPreview(upload, existing);
        });
    };
    const updateCardPreview = () => {
        const status = byId('webStatus').selectedOptions[0]?.textContent || 'PRÓXIMAMENTE';
        byId('webCardPreviewName').textContent = byId('webProjectName').textContent || 'Proyecto';
        byId('webCardPreviewCompany').textContent = byId('webProjectCompany').textContent || 'Empresa';
        byId('webCardPreviewDescription').textContent = byId('webDescription').value.trim() || 'Descripción comercial del proyecto.';
        byId('webCardPreviewBadge').textContent = byId('webBadge').value.trim() || status;
        byId('webCardPreviewStatus').textContent = status;
    };
    const fillForm = data => {
        clearErrors();
        byId('webProjectId').value = data.id;
        byId('webProjectName').textContent = data.name;
        byId('webProjectCompany').textContent = data.company;
        byId('webProjectLocation').textContent = data.location || 'Sin ubicación';
        byId('webShow').value = data.show_on_web ? '1' : '0';
        byId('webFeatured').value = data.featured_on_home ? '1' : '0';
        byId('webStatus').value = data.commercial_status;
        byId('webOrder').value = data.sort_order;
        byId('webDescription').value = data.short_description || '';
        byId('webImageAlt').value = data.image_alt || '';
        byId('webPlanImageAlt').value = data.plan_image_alt || '';
        byId('webPlanCaption').value = data.plan_caption || '';
        byId('webBadge').value = data.badge_text || '';
        resetUploads(data);
        updateCardPreview();
    };
    const fetchProject = async id => (await requestJson(`${baseUrl}/${id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })).data;
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
    const renderDetail = data => {
        const image = data.cover_image_url ? `<img class="web-project-detail-image mb-3" src="${escapeHtml(data.cover_image_url)}" alt="${escapeHtml(data.image_alt || data.name)}">` : '<div class="alert alert-light border">Sin portada configurada.</div>';
        const plan = data.plan_image_url ? `<hr><span class="web-label">PLANO CONFIGURADO</span><img class="web-project-detail-image web-project-detail-plan" src="${escapeHtml(data.plan_image_url)}" alt="${escapeHtml(data.plan_image_alt || data.name)}">` : '<hr><div class="alert alert-light border mb-0">Sin plano configurado.</div>';
        byId('projectWebDetail').innerHTML = `${image}<div class="web-detail-grid"><div><span class="web-label">PROYECTO</span><strong>${escapeHtml(data.name)}</strong></div><div><span class="web-label">EMPRESA</span><strong>${escapeHtml(data.company)}</strong></div><div><span class="web-label">PUBLICACIÓN</span><strong>${data.show_on_web ? 'Visible en web' : 'No publicado'}</strong></div><div><span class="web-label">ESTADO COMERCIAL</span><strong>${escapeHtml(data.commercial_status_label)}</strong></div><div><span class="web-label">DESTACADO</span><strong>${data.featured_on_home ? 'Sí' : 'No'}</strong></div><div><span class="web-label">ORDEN</span><strong>${data.sort_order}</strong></div></div>${data.short_description ? `<hr><p class="mb-0">${escapeHtml(data.short_description)}</p>` : ''}${plan}`;
    };
    const updateRow = data => {
        const row = document.querySelector(`[data-project-row="${data.id}"]`);
        if (!row) return;
        row.querySelector('[data-cell="web"]').innerHTML = `<span class="badge badge-${data.show_on_web ? 'success' : 'secondary'} px-3 py-2">${data.show_on_web ? 'PUBLICADO' : 'NO PUBLICADO'}</span>`;
        row.querySelector('[data-cell="status"]').textContent = data.commercial_status_label;
        row.querySelector('[data-cell="image"]').innerHTML = data.cover_image_url
            ? `<img class="web-project-thumb" src="${escapeHtml(data.cover_image_url)}" alt="">`
            : '<span class="web-project-no-image"><i class="far fa-image"></i></span>';
        row.querySelector('[data-cell="featured"]').textContent = data.featured_on_home ? 'Sí' : 'No';
        row.querySelector('[data-cell="order"]').textContent = data.sort_order;
    };

    uploads.forEach(upload => {
        const input = upload.querySelector('input');
        const choose = file => {
            if (!file) return;
            releasePreview(upload);
            const url = URL.createObjectURL(file);
            objectUrls.set(upload, url);
            setPreview(upload, url);
            upload.querySelector('[data-file-name]').textContent = file.name;
            upload.querySelector('[data-file-size]').textContent = file.size < 1048576 ? `${Math.max(1, Math.round(file.size / 1024))} KB` : `${(file.size / 1048576).toFixed(1)} MB`;
            upload.querySelector('[data-file-meta]').hidden = false;
            upload.classList.remove('has-error');
        };
        input.addEventListener('change', () => choose(input.files[0]));
        ['dragenter', 'dragover'].forEach(event => upload.addEventListener(event, e => { e.preventDefault(); upload.classList.add('is-dragging'); }));
        ['dragleave', 'drop'].forEach(event => upload.addEventListener(event, e => { e.preventDefault(); upload.classList.remove('is-dragging'); }));
        upload.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files; choose(file);
        });
        upload.querySelector('[data-file-remove]').addEventListener('click', () => {
            input.value = '';
            releasePreview(upload);
            upload.querySelector('[data-file-meta]').hidden = true;
            setPreview(upload, upload.dataset.existingUrl || '');
        });
    });

    ['webDescription', 'webBadge', 'webStatus'].forEach(id => byId(id).addEventListener('input', updateCardPreview));

    module.addEventListener('click', async event => {
        const edit = event.target.closest('.edit-web-project');
        const view = event.target.closest('.view-web-project');
        if (!edit && !view) return;
        const button = edit || view;
        button.disabled = true;
        try {
            const data = await fetchProject(button.dataset.id);
            if (edit) { fillForm(data); modal.modal('show'); } else { renderDetail(data); viewModal.modal('show'); }
        } catch (error) { notify('error', 'No se pudo abrir', error.message); }
        finally { button.disabled = false; }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        saving = true; clearErrors();
        const button = byId('saveProjectWeb');
        button.disabled = true; button.querySelector('i').className = 'fas fa-spinner fa-spin mr-1';
        const data = new FormData(form); data.append('_method', 'PUT');
        try {
            const result = await requestJson(`${baseUrl}/${byId('webProjectId').value}`, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            updateRow(result.data); modal.modal('hide'); await notify('success', 'Configuración guardada', result.message);
        } catch (error) {
            if (error.status === 422) showErrors(error.errors);
            else notify('error', 'No se pudo guardar', error.message);
        } finally {
            saving = false; button.disabled = false; button.querySelector('i').className = 'fas fa-save mr-1';
        }
    });

    modal.on('hidden.bs.modal', () => uploads.forEach(releasePreview));
})();
