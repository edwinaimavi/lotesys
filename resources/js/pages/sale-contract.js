document.addEventListener('DOMContentLoaded', () => {
    const $modal = $('#contractSaleModal');
    const $form = $('#contractSaleForm');
    const $button = $('#generateContractButton');
    let data = null;
    let generateUrl = '';
    let opening = false;
    let generating = false;

    const money = value => value === '' ? '' : 'S/ ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function showError(body) {
        const messages = Object.values(body.errors || {}).flat();
        Swal.fire('Contrato', messages[0] || body.message || 'No se pudo procesar el contrato.', 'error');
    }
    function responseError(response, body) {
        const messages = {
            401: 'Su sesión ha finalizado. Inicie sesión nuevamente.',
            403: 'No tiene permiso para acceder al módulo de ventas.',
            404: 'La venta solicitada no existe.',
            419: 'Su sesión ha caducado. Recargue la página e intente nuevamente.',
            500: 'No se pudo procesar el contrato. Intente nuevamente o consulte al administrador.'
        };
        return messages[response.status] ? { message: messages[response.status] } : body;
    }

    $(document).on('click', '.generateSaleContract', async function () {
        if (opening || generating) return;
        opening = true;
        const $trigger = $(this).prop('disabled', true);
        try {
            const response = await fetch(this.dataset.contractData, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const body = await response.json();
            if (!response.ok) { showError(responseError(response, body)); return; }
            data = body;
            generateUrl = this.dataset.contractGenerate;
            $('#contractSaleCode').text(body.sale_code);
            $('#contractCustomerName').text(body.values.full_name);
            $('#contractCompanyProject').text([body.values.company_business_name, body.values.project_name].filter(Boolean).join(' · '));
            $('#contractWarning').text(body.template.warning);
            const $fields = $('#contractFields').empty();
            const groups = new Map();
            Object.entries(body.fields).forEach(([key, field]) => {
                const quota = /^Cuota (\d+)$/.exec(field.group);
                if (quota && Number(quota[1]) > body.schedule_count) return;
                if (!groups.has(field.group)) {
                    const $section = $('<section class="card border-0 shadow-sm mb-3">');
                    $section.append($('<div class="card-header bg-white py-2 font-weight-bold">').text(field.group));
                    const $row = $('<div class="card-body p-3"><div class="form-row"></div></div>');
                    $section.append($row);
                    $fields.append($section);
                    groups.set(field.group, $row.find('.form-row'));
                }
                const $cell = $('<div class="form-group col-md-6 col-lg-4 mb-3">');
                const id = 'contract_' + key;
                $cell.append($('<label class="small mb-1">').attr('for', id).text(field.label));
                let $input;
                if (field.type === 'gender') {
                    $input = $('<select>').append(
                        $('<option value="">').text('-- Seleccionar --'),
                        $('<option value="masculino">').text('Masculino'),
                        $('<option value="femenino">').text('Femenino'),
                        $('<option value="no_especificado">').text('No especificado')
                    );
                } else if (field.type === 'textarea') {
                    $input = $('<textarea rows="3">');
                    $cell.removeClass('col-lg-4').addClass('col-lg-12');
                } else {
                    $input = $('<input>').attr('type', field.type === 'date' ? 'date' : 'text');
                    if (field.type === 'money') $input.attr('inputmode', 'decimal');
                }
                $input.addClass('form-control form-control-sm').attr({ id, name: key, maxlength: 2000 }).val(body.values[key]);
                if (body.values[key] === '') $input.addClass('border-warning').attr('placeholder', 'Sin datos · completar si corresponde');
                $cell.append($input, $('<div class="invalid-feedback">').attr('id', id + '_error'));
                if (field.type === 'money') {
                    const $formatted = $('<small class="text-muted d-block">').text(money(body.values[key]));
                    $cell.append($formatted);
                    $input.on('input', () => $formatted.text(money($input.val())));
                }
                if (!field.in_template) $cell.append($('<small class="text-muted d-block">').text('Dato de apoyo; sin espacio en la plantilla actual.'));
                groups.get(field.group).append($cell);
            });
            $modal.modal('show');
        } catch (_) {
            showError({ message: 'No se pudieron cargar los datos del contrato. Verifique su sesión e intente nuevamente.' });
        } finally {
            opening = false;
            $trigger.prop('disabled', false);
        }
    });

    $form.on('change', '#contract_gender', function () {
        Object.entries(data.gender_defaults[this.value] || {}).forEach(([key, value]) => {
            $form.find('[name="' + key + '"]').val(value).removeClass('border-warning');
        });
    });
    $form.on('input', '[name]', function () {
        const principal = ['block_name', 'lot_number', 'lot_code', 'lot_area', 'lot_unit_measure'];
        const key = this.name.replace(/^lot_01_/, '');
        if (principal.includes(key)) {
            const other = this.name === key ? 'lot_01_' + key : key;
            $form.find('[name="' + other + '"]').val(this.value);
        }
    });
    $modal.on('hide.bs.modal', event => { if (generating) event.preventDefault(); });
    $form.on('submit', async event => {
        event.preventDefault();
        if (generating || !data) return;
        generating = true;
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Generando contrato...');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        const values = { ...data.values };
        $form.find('[name]').each(function () { if (this.name !== '_token') values[this.name] = this.value; });
        try {
            const response = await fetch(generateUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': $form.find('[name="_token"]').val() },
                body: JSON.stringify({ values })
            });
            if (!response.ok) {
                const body = await response.json();
                Object.entries(body.errors || {}).forEach(([key, messages]) => {
                    const name = key.replace(/^values\./, '');
                    $form.find('[name="' + name + '"]').addClass('is-invalid');
                    document.getElementById('contract_' + name + '_error')?.replaceChildren(document.createTextNode(messages[0]));
                });
                showError(responseError(response, body));
                return;
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const disposition = response.headers.get('Content-Disposition') || '';
            const match = /filename="?([^";]+)"?/.exec(disposition);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = match ? match[1] : 'Contrato_Grupo_Krea.docx';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            setTimeout(() => URL.revokeObjectURL(url), 60000);
        } catch (_) {
            showError({ message: 'No se pudo descargar el contrato. Verifique su sesión e intente nuevamente.' });
        } finally {
            generating = false;
            $button.prop('disabled', false).html('<i class="fas fa-file-word mr-1"></i> Generar Word');
        }
    });
});
