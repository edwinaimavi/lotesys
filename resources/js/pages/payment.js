var divLoading = document.getElementById('divLoading');

let tablePayment;

let paymentReceiptFiles = [];
let paymentExistingReceiptCount = 0;
let paymentEvidenceRequest = null;
let paymentDetailEvidenceRequest = null;

function resetPaymentReceipts() {
    if (paymentEvidenceRequest) paymentEvidenceRequest.abort();
    paymentReceiptFiles.forEach(item => URL.revokeObjectURL(item.url));
    paymentReceiptFiles = [];
    paymentExistingReceiptCount = 0;
    $('#receipts').val('');
    $('#paymentReceiptPreviews, #paymentExistingReceipts, #receipts-error').empty();
    $('#btnSavePayment').prop('disabled', false);
}

function openPaymentReceipt(url, name, source) {
    const viewer = document.getElementById('paymentReceiptViewer');
    // Dentro del modal activo para conservar su foco y su scroll.
    $(source).closest('.modal').append(viewer);
    $('#paymentReceiptViewerTitle').text(name);
    $('#paymentReceiptViewerImage').attr('src', url);
    viewer.showModal();
}

function receiptCard(item, source, remove) {
    const card = $('<div>', { class: 'payment-receipt-card' });
    const isImage = item.mime_type.startsWith('image/');
    let open;
    if (isImage) {
        open = $('<button>', { type: 'button', class: 'payment-receipt-open', 'aria-label': 'Ampliar ' + item.name });
        open.append($('<img>', { src: item.url, alt: item.name, loading: 'lazy' }));
        open.on('click', function () { openPaymentReceipt(item.url, item.name, this); });
    } else {
        open = $('<a>', { href: item.url, target: '_blank', rel: 'noopener noreferrer', class: 'payment-receipt-open' });
        open.append($('<span>').append($('<i>', { class: 'fas fa-file-pdf mr-1', 'aria-hidden': 'true' })).append(document.createTextNode('Ver PDF')));
    }
    card.append(open, $('<span>', { class: 'payment-receipt-name', title: item.name }).text(item.name));
    card.append($('<small>', { class: 'text-muted' }).text((item.file_size / 1024).toFixed(1) + ' KB'));
    if (remove) {
        card.append($('<button>', { type: 'button', class: 'payment-receipt-remove', 'aria-label': 'Quitar ' + item.name })
            .text('×').on('click', remove));
    }
    $(source).append(card);
}

function renderPaymentReceiptPreviews() {
    $('#paymentReceiptPreviews').empty();
    paymentReceiptFiles.forEach((item, index) => receiptCard({
        name: item.file.name, file_size: item.file.size, mime_type: item.file.type, url: item.url
    }, '#paymentReceiptPreviews', () => {
        URL.revokeObjectURL(item.url);
        paymentReceiptFiles.splice(index, 1);
        $('#receipts-error').empty();
        renderPaymentReceiptPreviews();
    }));
}

$(document).on('change', '#receipts', function () {
    const files = Array.from(this.files || []);
    this.value = '';
    const allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    let error = '';
    if (files.length + paymentReceiptFiles.length + paymentExistingReceiptCount > 10) {
        error = 'Se permiten como máximo 10 comprobantes por pago.';
    } else if (files.some(file => file.size > 5 * 1024 * 1024 || !allowed.includes(file.type) || !/\.(jpe?g|png|webp|pdf)$/i.test(file.name))) {
        error = 'Seleccione JPG, PNG, WEBP o PDF de hasta 5 MB por archivo.';
    }
    $('#receipts-error').text(error);
    if (error) return;
    files.forEach(file => paymentReceiptFiles.push({ file, url: URL.createObjectURL(file) }));
    renderPaymentReceiptPreviews();
});

$(document).on('click', '.payment-receipt-close', function () {
    document.getElementById('paymentReceiptViewer').close();
});

function loadPaymentEvidence(id, editing) {
    if (editing && paymentEvidenceRequest) paymentEvidenceRequest.abort();
    if (!editing && paymentDetailEvidenceRequest) paymentDetailEvidenceRequest.abort();
    const target = editing ? '#paymentExistingReceipts' : '#vp_receipts';
    $(target).empty();
    if (editing) {
        $('#btnSavePayment').prop('disabled', true);
    } else {
        $('#vp_origin_bank_row, #vp_operation_row').prop('hidden', true);
        $('#vp_receipts_message').text('Cargando comprobantes…');
    }
    const request = $.getJSON(window.routes.paymentEvidence.replace(':id', encodeURIComponent(id)))
        .done(data => {
            if (editing) {
                const bank = data.origin_bank || '';
                const known = $('#origin_bank option').toArray().some(option => option.value === bank);
                $('#origin_bank').val(known ? bank : 'Otra entidad');
                $('#origin_bank_other').val(known ? '' : bank);
                $('#operation_number').val(data.operation_number || '');
                paymentExistingReceiptCount = data.receipts.length;
                togglePaymentFields();
                $('#btnSavePayment').prop('disabled', false);
            } else {
                const bank = data.origin_bank || data.historical_bank;
                $('#vp_origin_bank_row').prop('hidden', !bank);
                $('#vp_origin_bank_label').text(data.origin_bank ? 'Banco de origen' : 'Banco registrado (histórico)');
                $('#vp_origin_bank').text(bank || '');
                $('#vp_operation_number').text(data.operation_number || '');
                $('#vp_operation_row').prop('hidden', !data.operation_number);
                $('#vp_receipts_message').text(data.receipts.length ? '' : 'No se adjuntaron comprobantes de pago.');
            }
            data.receipts.forEach(item => receiptCard(item, target));
        }).fail((xhr, status) => {
            if (status === 'abort') return;
            const message = 'No se pudieron cargar los comprobantes. Cierre y vuelva a abrir el pago.';
            $(editing ? '#receipts-error' : '#vp_receipts_message').text(message);
        });
    if (editing) paymentEvidenceRequest = request;
    else paymentDetailEvidenceRequest = request;
}

function formatPaymentDateForDisplay(dateValue) {
    const dateParts = String(dateValue || '').split('T')[0].split('-');

    if (dateParts.length !== 3) {
        return dateValue || 'â€”';
    }

    return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

function paymentSaleSelectData(state) {

    if (!state || !state.element) {
        return null;
    }

    const $option = $(state.element);

    return {
        saleCode: $option.data('sale-code') || '',
        customer: $option.data('customer-short') || '',
        project: $option.data('project') || '',
        company: $option.data('company') || '',
        lotCount: parseInt($option.data('lot-count'), 10) || 1,
        primaryLot: $option.data('primary-lot') || '',
        multiple: String($option.data('multiple')) === '1'
    };
}

function paymentSaleSelectTemplate(state, compact = false) {

    if (!state.id) {
        return state.text;
    }

    const data = paymentSaleSelectData(state);

    if (!data) {
        return state.text;
    }

    const $wrapper = $('<div>', {
        class: compact
            ? 'payment-sale-choice payment-sale-choice--selected'
            : 'payment-sale-choice'
    });

    const $top = $('<div>', { class: 'payment-sale-choice__top' });
    $('<span>', {
        class: 'payment-sale-choice__main',
        text: data.multiple
            ? `${data.saleCode} · ${data.customer} · ${data.lotCount} lotes`
            : `${data.saleCode} · ${data.customer} · ${data.primaryLot}`
    }).appendTo($top);

    if (data.multiple) {
        $('<span>', {
            class: 'payment-sale-choice__badge',
            text: 'MÚLTIPLE'
        }).appendTo($top);
    }

    $wrapper.append($top);

    $('<div>', {
        class: 'payment-sale-choice__meta',
        text: `${data.project} · ${data.company}`
    }).appendTo($wrapper);

    return $wrapper;
}

function initPaymentSelect2() {

    const $sale = $('#sale_id');

    if (
        !$sale.length ||
        typeof $.fn.select2 === 'undefined'
    ) {
        return;
    }

    if ($sale.hasClass('select2-hidden-accessible')) {
        $sale.select2('destroy');
    }

    $sale.select2({
        width: '100%',
        dropdownParent: $('#paymentModal'),
        placeholder: 'Seleccione una venta',
        templateResult: state => paymentSaleSelectTemplate(state, false),
        templateSelection: state => paymentSaleSelectTemplate(state, true)
    });

    $sale.next('.select2-container').addClass('payment-sale-select2');
}

document.addEventListener("DOMContentLoaded", function () {

    // =========================================================
    // CSRF TOKEN
    // =========================================================

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    initPaymentSelect2();

    // =========================================================
    // DATATABLE
    // =========================================================

    tablePayment = $('#tablePayment').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.paymentList,

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            {
                data: 'id',
                name: 'id'
            },

            {
                data: 'sale',
                name: 'sale'
            },

            {
                data: 'company',
                name: 'company'
            },

            {
                data: 'property',
                name: 'property'
            },

            {
                data: 'installment',
                name: 'installment'
            },

            {
                data: 'payment_type',
                name: 'payment_type'
            },

            {
                data: 'payment_date',
                name: 'payment_date'
            },

            {
                data: 'amount',
                name: 'amount'
            },

            {
                data: 'late_fee_paid',
                name: 'late_fee_paid'
            },

            {
                data: 'payment_method',
                name: 'payment_method'
            },

            {
                data: 'status',
                name: 'status'
            },

            {
                data: 'acciones',
                name: 'acciones',
                orderable: false,
                searchable: false
            }

        ],

        responsive: true,

        autoWidth: false,

        language: {
            url: "/vendor/datatables/js/i18n/es-ES.json"
        },

        dom: `
        <'row mb-3'
            <'col-sm-12 col-md-6'l>
            <'col-sm-12 col-md-6 text-md-end'f>
        >

        <'row'
            <'col-sm-12'tr>
        >

        <'row mt-3'
            <'col-sm-12 col-md-5'i>
            <'col-sm-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>
        >

        <'row mt-3'
            <'col-sm-12 text-center'B>
        >
        `,

        buttons: [

            {
                extend: 'excel',
                className: 'btn btn-success btn-sm',
                text: '<i class="fas fa-file-excel"></i> Excel'
            },

            {
                extend: 'pdf',
                className: 'btn btn-danger btn-sm',
                text: '<i class="fas fa-file-pdf"></i> PDF'
            },

            {
                extend: 'print',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="fas fa-print"></i> Print'
            }

        ],

        preDrawCallback: function () {

            divLoading && divLoading.classList.remove('d-none');

        },

        drawCallback: function () {

            divLoading && divLoading.classList.add('d-none');

        }

    });

    // =========================================================
    // GUARDAR / ACTUALIZAR
    // =========================================================

    $('#paymentForm').on('submit', function (e) {

        e.preventDefault();

        if (!$(this).attr('data-id') && !validatePaymentDetails()) {
            return;
        }

        const btn = $('#btnSavePayment');

        if (btn.prop('disabled')) {
            return;
        }

        btn.prop('disabled', true);

        btn.html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Guardando...
        `);

        divLoading.style.display = "flex";

        const $form = $(this);

        const id = $form.attr('data-id');

        let url = '';

        let type = '';

        const formData = new FormData(this);
        formData.delete('receipts[]');
        paymentReceiptFiles.forEach(item => formData.append('receipts[]', item.file));

        if (id) {

            url = "/admin/payments/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storePayment;

            type = 'POST';

        }

        $.ajax({

            url: url,

            type: type,

            data: formData,

            processData: false,

            contentType: false,

            success: function (response) {

                divLoading.style.display = "none";

                btn.prop('disabled', false);

                btn.html(`
                    <i class="fas fa-save mr-1"></i>
                    Guardar Pago
                `);

                $('#paymentModal').modal('hide');

                tablePayment.ajax.reload(null, false);

                Swal.fire({

                    title: response.message,

                    icon: "success",

                    toast: true,

                    position: "top-end",

                    showConfirmButton: false,

                    timer: 3000,

                    timerProgressBar: true

                });

            },

            error: function (xhr) {

                divLoading.style.display = "none";

                btn.prop('disabled', false);

                btn.html(`
                    <i class="fas fa-save mr-1"></i>
                    Guardar Pago
                `);

                if (xhr.status === 422) {

                    const errors = xhr.responseJSON.errors || {};
                    const firstMessage = Object.values(errors)[0]?.[0];

                    $('.is-invalid').removeClass('is-invalid');

                    $('.invalid-feedback').text('');

                    $.each(errors, function (key, messages) {

                        const input = $(document.getElementById(key));

                        input.addClass('is-invalid');

                        $(document.getElementById(key + '-error')).text(messages[0]);

                    });

                    if (firstMessage) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Revise el pago',
                            text: firstMessage,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000
                        });
                    }

                } else {

                    Swal.fire({

                        icon: 'error',

                        title: 'Error',

                        text: xhr.responseJSON?.message || 'Unexpected error',

                        toast: true,

                        position: 'top-end',

                        showConfirmButton: false,

                        timer: 3500

                    });

                }

            }

        });

    });

    // =========================================================
    // EDITAR
    // =========================================================

    $(document).on('click', '.editPayment', function () {

        const id = $(this).data('id');

        $('#paymentForm').attr('data-id', id);

        $('#sale_id').val($(this).data('sale_id'));

        $('#payment_schedule_id').val($(this).data('payment_schedule_id'));

        $('#payment_type').val($(this).data('payment_type'));

        $('#payment_date').val($(this).data('payment_date'));

        $('#amount').val($(this).data('amount'));

        $('#late_fee_paid').val($(this).data('late_fee_paid'));

        $('#discount').val($(this).data('discount'));

        $('#observation').val($(this).data('observation'));

        $('#payment_method').val($(this).data('payment_method'));

        $('#operation_number').val($(this).data('operation_number'));

        $('#status').val($(this).data('status'));
        resetPaymentReceipts();
        loadPaymentEvidence(id, true);

        $('.icon_modal').html(`
            <i class="far fa-edit text-primary"></i>
        `);

        $('#paymentModalLabel').html('EDITAR PAGO');

        $('#paymentModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#paymentModal').on('hidden.bs.modal', function () {

        const $form = $('#paymentForm');

        // Limpiar formulario
        $form[0].reset();
        $form.removeAttr('data-id');
        resetPaymentReceipts();

        // Restaurar título
        $('#paymentModalLabel').html('NUEVO PAGO');

        // Limpiar validaciones
        $form.find('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // ------------------------------------------
        // LIMPIAR COMBOS
        // ------------------------------------------
        $('#sale_id').val('').trigger('change');

        $('#payment_schedule_id').html(`
        <option value="">Seleccione una cuota</option>
    `);

        // ------------------------------------------
        // LIMPIAR TABLA DE DETALLE DE CUOTAS
        // ------------------------------------------
        $('#paymentDetailsBody').html(`
        <tr class="text-center text-muted empty-row">
            <td colspan="5">
                No hay cuotas agregadas
            </td>
        </tr>
    `);




        // ------------------------------------------
        // RESETEAR MONTOS
        // ------------------------------------------
        $('#amount').val('0.00');
        $('#late_fee_paid').val('0');
        $('#discount').val('0');

        // ------------------------------------------
        // OCULTAR CAMPOS BANCARIOS
        // ------------------------------------------
        $('#bank_container').hide();
        $('#operation_container').hide();

        $('#origin_bank, #origin_bank_other').val('');
        $('#operation_number').val('');

    });

    // =========================================================
    // ELIMINAR
    // =========================================================

    // =========================================================
    // ANULAR PAGO
    // =========================================================

    $(document).on('click', '.cancelPayment', function () {

        const button = $(this);
        const id = button.data('id');
        const hasAcceptedCpe = Number(button.attr('data-has-accepted-cpe')) === 1;
        const hasPendingCpe = Number(button.attr('data-has-pending-cpe')) === 1;

        if (hasPendingCpe && !hasAcceptedCpe) {
            Swal.fire({
                icon: 'warning',
                title: 'Comprobante pendiente',
                text: 'Este pago tiene un comprobante SUNAT pendiente. Revise su estado antes de anular el pago.'
            });
            return;
        }

        if (hasAcceptedCpe) {
            const cpeLabel = button.attr('data-cpe-label') || 'Comprobante SUNAT';
            const customer = button.attr('data-cpe-customer') || '—';
            const rawAmount = parseFloat(button.attr('data-cpe-amount'));
            const amount = Number.isFinite(rawAmount)
                ? `S/ ${rawAmount.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                : '—';

            Swal.fire({
                title: 'Anular pago y comprobante',
                html: `
                    <div class="text-left border rounded p-3 mb-3 bg-light">
                        <div class="mb-2"><strong>Comprobante:</strong> ${escapePaymentHtml(cpeLabel)}</div>
                        <div class="mb-2"><strong>Cliente:</strong> ${escapePaymentHtml(customer)}</div>
                        <div class="mb-2"><strong>Monto:</strong> ${escapePaymentHtml(amount)}</div>
                        <div><strong>Motivo SUNAT:</strong> 01 - Anulación de la operación</div>
                    </div>
                    <div class="text-left small text-muted">
                        Primero se emitirá una Nota de Crédito. El pago y el cronograma
                        solo se revertirán si SUNAT confirma la Nota de Crédito.
                    </div>
                `,
                input: 'textarea',
                inputLabel: 'Motivo / sustento de la anulación',
                inputPlaceholder: 'Ej.: Pago registrado por error...',
                inputAttributes: {
                    maxlength: 500,
                    'aria-label': 'Motivo de anulación'
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Emitir NC y anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                focusConfirm: false,
                inputValidator: (value) => {
                    const reason = String(value || '').trim();
                    if (reason.length < 5) {
                        return 'Ingrese un motivo de al menos 5 caracteres.';
                    }
                    if (reason.length > 500) {
                        return 'El motivo no puede superar los 500 caracteres.';
                    }
                    return null;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitPaymentCancellation(id, String(result.value || '').trim());
                }
            });

            return;
        }

        Swal.fire({
            title: '¿Anular pago?',
            html: `
                Esta acción revertirá:
                <br><br>
                • cuotas pagadas
                <br>
                • saldos
                <br>
                • estados del cronograma
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                submitPaymentCancellation(id);
            }
        });

    });

    function submitPaymentCancellation(id, cancellationReason = null) {
        const data = {};

        if (cancellationReason) {
            data.cancellation_reason = cancellationReason;
        }

        $.ajax({
            url: `/admin/payments/${id}/cancel`,
            type: 'POST',
            data: data,
            beforeSend: function () {
                Swal.fire({
                    title: cancellationReason
                        ? 'Procesando Nota de Crédito...'
                        : 'Anulando pago...',
                    text: 'Espere un momento.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function (response) {
                tablePayment.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Operación completada',
                    text: response.message,
                    confirmButtonText: 'Aceptar'
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: xhr.status === 409 ? 'warning' : 'error',
                    title: xhr.status === 409 ? 'Revisión necesaria' : 'No se pudo anular',
                    text: xhr.responseJSON?.message || 'Error al anular pago'
                });
            }
        });
    }

    function escapePaymentHtml(value) {
        return $('<div>').text(String(value ?? '')).html();
    }

    // =========================================================
    // VER DETALLE
    // =========================================================

    $(document).on('click', '.viewPayment', function () {

        const $button = $(this);
        const status = String($button.data('status') || '').toLowerCase();
        const amount = parseFloat($button.data('amount') || 0);
        const lateFee = parseFloat($button.data('late_fee_paid') || 0);
        const discount = parseFloat($button.data('discount') || 0);

        let badgeClass = 'badge-secondary';

        if (status === 'activo') {
            badgeClass = 'badge-success';
        }

        if (status === 'anulado') {
            badgeClass = 'badge-danger';
        }

        const formatLabel = (value) => String(value || '—')
            .replaceAll('_', ' ')
            .replace(/\b\w/g, letter => letter.toUpperCase());

        $('#vp_id').text($button.data('id') || '—');
        $('#vp_sale').text($button.data('sale') || '—');
        $('#vp_customer').text($button.data('customer') || '—');
        $('#vp_company').text($button.data('company') || '—');
        $('#vp_company_ruc').text($button.data('company_ruc') || '—');
        $('#vp_project').text($button.data('project') || '—');
        $('#vp_block').text($button.data('block') || '—');
        $('#vp_lot_number').text($button.data('lot_number') || '—');
        $('#vp_lot_code').text($button.data('lot_code') || '—');
        $('#vp_schedule').text($button.data('installment') || '—');
        $('#vp_payment_date').text(formatPaymentDateForDisplay($button.data('payment_date')));
        $('#vp_payment_type').text(formatLabel($button.data('payment_type')));
        $('#vp_payment_method').text(formatLabel($button.data('payment_method')));
        $('#vp_operation_number').text($button.data('operation_number') || '—');
        $('#vp_observation').text($button.data('observation') || 'Sin observación');

        $('#vp_amount').text('S/ ' + amount.toFixed(2));
        $('#vp_late_fee').text('S/ ' + lateFee.toFixed(2));
        $('#vp_discount').text('S/ ' + discount.toFixed(2));

        $('#vp_status')
            .removeClass('badge-success badge-danger badge-secondary')
            .addClass(badgeClass)
            .text(status ? status.toUpperCase() : '—');

        $('#vp_created_by').text($button.data('created_by') || '—');
        $('#vp_updated_by').text($button.data('updated_by') || '—');
        $('#vp_created_at').text($button.data('created_at') || '—');
        $('#vp_updated_at').text($button.data('updated_at') || '—');

        loadPaymentEvidence($button.data('id'), false);
        $('#viewPaymentModal').modal('show');

    });

});

// =========================================================
// CARGAR CUOTAS SEGÚN VENTA
// =========================================================

$('#sale_id').on('change', function () {

    const saleId = $(this).val();

    $('#payment_schedule_id').html(`
        <option value="">Cargando cuotas...</option>
    `);

    if (!saleId) {

        $('#payment_schedule_id').html(`
            <option value="">Seleccione una cuota</option>
        `);

        return;
    }

    $.ajax({
        url: `${window.routes.paymentSchedules}/${saleId}`,
        type: 'GET',
        success: function (response) {

            let options = `
                <option value="">Seleccione una cuota</option>
            `;

            response.forEach(schedule => {

                const pendingAmount = parseFloat(
                    schedule.pending_amount ?? schedule.total_amount ?? 0
                );
                const totalReal =
                    pendingAmount +
                    parseFloat(schedule.late_fee || 0);

                const installmentNumber =
                    parseInt(schedule.installment_number, 10);
                const visualNumber = installmentNumber + 1;
                const installmentLabel = schedule.installment_label ||
                    (installmentNumber === 0
                        ? `Cuota ${visualNumber} - Inicial`
                        : `Cuota ${visualNumber}`);

                options += `
                    <option 
                        value="${schedule.id}"
                        data-pending-amount="${pendingAmount}"
                        data-late_fee="${schedule.late_fee}"
                        data-installment-label="${installmentLabel}"
                    >
                        ${installmentLabel}
                        - Vence: ${formatPaymentDateForDisplay(schedule.due_date)}
                        - Total: S/ ${totalReal.toFixed(2)}
                        - Mora: S/ ${parseFloat(schedule.late_fee || 0).toFixed(2)}
                        - Saldo: S/ ${pendingAmount.toFixed(2)}
                    </option>
                `;
            });

            $('#payment_schedule_id').html(options);

            $('#payment_schedule_id').off('change.autoLateFee');

            $('#payment_schedule_id').on('change.autoLateFee', function () {

                const selected = $(this).find(':selected');

                const lateFee = parseFloat(selected.data('late_fee') || 0);

                if (!$('#paymentDetailsBody tr[data-schedule-id]').length) {
                    $('#late_fee_paid').val(lateFee.toFixed(2));
                }

                recalculateTotalPayment();
            });
        },
        error: function () {
            $('#payment_schedule_id').html(`
                <option value="">Error al cargar cuotas</option>
            `);
        }
    });
});
// =========================================================
// PAYMENT DETAILS DINÁMICO
// =========================================================

function recalculateTotalPayment() {

    let total = 0;

    // =========================================
    // SUMAR CUOTAS
    // =========================================

    $('.applied-amount').each(function () {

        total += parseFloat($(this).val()) || 0;

    });

    // =========================================
    // SUMAR MORA
    // =========================================

    total += parseFloat(
        $('#late_fee_paid').val() || 0
    );

    $('#amount').val(
        total.toFixed(2)
    );

}

function syncPrincipalLateFee() {

    const firstRow = $('#paymentDetailsBody tr[data-schedule-id]').first();

    const lateFee = firstRow.length
        ? parseFloat(firstRow.attr('data-late-fee') || 0)
        : 0;

    $('#late_fee_paid').val(lateFee.toFixed(2));
}

function validatePaymentDetails() {

    const rows = $('#paymentDetailsBody tr[data-schedule-id]');

    if (!rows.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Agregue al menos una cuota',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        return false;
    }

    let errorMessage = '';

    rows.each(function () {
        const row = $(this);
        const label = row.attr('data-installment-label') || 'la cuota';
        const pendingAmount = parseFloat(
            row.attr('data-pending-amount') || 0
        );
        const appliedAmount = parseFloat(
            row.find('.applied-amount').val() || 0
        );

        if (appliedAmount <= 0) {
            errorMessage = `El monto aplicado a ${label} debe ser mayor que cero.`;
            return false;
        }

        if (appliedAmount - pendingAmount > 0.01) {
            errorMessage = `El monto aplicado a ${label} no puede superar ` +
                `su saldo de S/ ${pendingAmount.toFixed(2)}.`;
            return false;
        }
    });

    recalculateTotalPayment();

    if (!errorMessage && parseFloat($('#amount').val() || 0) <= 0) {
        errorMessage = 'El monto total del pago debe ser mayor que cero.';
    }

    if (errorMessage) {
        Swal.fire({
            icon: 'warning',
            title: 'Revise el pago',
            text: errorMessage,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });

        return false;
    }

    return true;
}

// =========================================================
// BOTÓN AGREGAR CUOTA
// =========================================================

$(document).on('click', '#btnAddInstallment', function () {

    const selected = $('#payment_schedule_id').find(':selected');

    const scheduleId = selected.val();

    if (!scheduleId) {

        Swal.fire({

            icon: 'warning',

            title: 'Seleccione una cuota',

            toast: true,

            position: 'top-end',

            showConfirmButton: false,

            timer: 2500

        });

        return;
    }

    // evitar duplicados
    if ($(`tr[data-schedule-id="${scheduleId}"]`).length) {

        Swal.fire({

            icon: 'warning',

            title: 'La cuota ya fue agregada',

            toast: true,

            position: 'top-end',

            showConfirmButton: false,

            timer: 2500

        });

        return;
    }

    $('.empty-row').remove();

    const installment = selected.attr('data-installment-label') ||
        selected.text().trim();

    const balance = parseFloat(
        selected.data('pending-amount') || 0
    );

    const lateFee = parseFloat(
        selected.data('late_fee') || 0
    );

    const row = `
        <tr data-schedule-id="${scheduleId}"
            data-installment-label="${installment}"
            data-pending-amount="${balance.toFixed(2)}"
            data-late-fee="${lateFee.toFixed(2)}">

            <td>

                ${installment}

                <input type="hidden"
                    name="payment_details[${scheduleId}][payment_schedule_id]"
                    value="${scheduleId}">

            </td>

            <td class="text-center">

                S/ ${balance.toFixed(2)}

            </td>

            <td>

                <div class="input-group input-group-sm">

                    <div class="input-group-prepend">
                        <span class="input-group-text">S/</span>
                    </div>

                    <input type="number"
                        step="0.01"
                        min="0.01"
                        max="${balance.toFixed(2)}"
                        inputmode="decimal"
                        aria-label="Monto aplicado a ${installment}"
                        class="form-control form-control-sm text-right applied-amount"
                        name="payment_details[${scheduleId}][applied_amount]"
                        value="${balance.toFixed(2)}">

                </div>

            </td>

            <td class="text-center text-danger">

                S/ ${lateFee.toFixed(2)}

            </td>

            <td class="text-center">

                <button type="button"
                    class="btn btn-sm btn-danger remove-detail">

                    <i class="fas fa-trash"></i>

                </button>

            </td>

        </tr>
    `;

    $('#paymentDetailsBody').append(row);

    syncPrincipalLateFee();
    recalculateTotalPayment();

});

// =========================================================
// ELIMINAR DETALLE
// =========================================================

$(document).on('click', '.remove-detail', function () {

    $(this).closest('tr').remove();

    if ($('#paymentDetailsBody tr').length === 0) {

        $('#paymentDetailsBody').html(`
            <tr class="text-center text-muted empty-row">

                <td colspan="5">
                    No hay cuotas agregadas
                </td>

            </tr>
        `);

    }

    syncPrincipalLateFee();
    recalculateTotalPayment();

});

// =========================================================
// RECALCULAR TOTAL
// =========================================================

$(document).on('keyup change', '.applied-amount', function () {

    recalculateTotalPayment();

});

$(document).on('keyup change', '#late_fee_paid', function () {

    recalculateTotalPayment();

});

$(document).on('change blur', '.applied-amount', function () {

    const input = $(this);
    const pendingAmount = parseFloat(input.attr('max') || 0);
    const appliedAmount = parseFloat(input.val() || 0);

    if (appliedAmount - pendingAmount > 0.01) {
        input.val(pendingAmount.toFixed(2));
        recalculateTotalPayment();

        Swal.fire({
            icon: 'warning',
            title: 'Monto ajustado',
            text: `No puede aplicar más de S/ ${pendingAmount.toFixed(2)} a esta cuota.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500
        });
    }
});

function togglePaymentFields() {
    const method = $('#payment_method').val();
    const transfer = method === 'transferencia';
    const reference = ['transferencia', 'deposito', 'yape', 'plin'].includes(method);
    $('#bank_container').toggle(transfer);
    $('#origin_bank').prop('required', transfer).prop('disabled', !transfer);
    $('#operation_container').toggle(reference);
    $('#operation_number').prop('required', reference).prop('disabled', !reference);
    if (!transfer) $('#origin_bank, #origin_bank_other').val('');
    if (!reference) $('#operation_number').val('');
    const other = transfer && $('#origin_bank').val() === 'Otra entidad';
    $('#origin_bank_other_container').toggle(other);
    $('#origin_bank_other').prop('required', other).prop('disabled', !other);
    if (!other) $('#origin_bank_other').val('');
    const placeholders = {
        transferencia: 'Número de operación bancaria',
        deposito: 'Número de operación o depósito',
        yape: 'Número de operación Yape',
        plin: 'Número de operación Plin'
    };
    $('#operation_number').attr('placeholder', placeholders[method] || '');
}

$(document).on('change', '#origin_bank', togglePaymentFields);
/*
|--------------------------------------------------------------------------
| CAMBIO MÉTODO PAGO
|--------------------------------------------------------------------------
*/
$(document).on('change', '#payment_method', function () {

    togglePaymentFields();

});

/*
|--------------------------------------------------------------------------
| ABRIR MODAL
|--------------------------------------------------------------------------
*/
$('#paymentModal').on('shown.bs.modal', function () {

    initPaymentSelect2();

    togglePaymentFields();



    // =========================================================
    // ABRIR MODAL FACTURACION
    // =========================================================


});


$(document).on('click', '.generateInvoice', function () {

    const paymentId = $(this).data('payment_id');
    const saleId = $(this).data('sale_id');

    const amount = parseFloat(
        $(this).data('amount') || 0
    );

    const paymentType =
        $(this).data('payment_type') || '';

    // =========================================
    // LIMPIAR DATOS PREVIOS
    // =========================================

    $('#invoiceForm')[0].reset();
    resetInvoiceMultipleLotsSummary();

    $('#company_id').val('');
    $('#company_name').val('');
    $('#series').val('');
    $('#number').val('');

    // =========================================
    // IDS
    // =========================================

    $('#invoice_payment_id').val(paymentId);
    $('#invoice_sale_id').val(saleId);

    // =========================================
    // MONTO
    // =========================================

    $('#total_amount').val(
        amount.toFixed(2)
    );

    $('#invoice_total_preview').text(
        'S/ ' + amount.toFixed(2)
    );

    $('#subtotal').val(
        amount.toFixed(2)
    );

    $('#tax_amount').val(
        '0.00'
    );

    $('#total_amount').val(
        amount.toFixed(2)
    );

    // =========================================
    // RESUMEN FINANCIERO
    // =========================================

    $('#subtotal_preview').text(
        'S/ ' + amount.toFixed(2)
    );

    $('#igv_preview').text(
        'S/ 0.00'
    );

    $('#total_preview').text(
        'S/ ' + amount.toFixed(2)
    );

    // =========================================
    // CONCEPTO
    // =========================================

    let concept = 'installment';

    if (paymentType === 'inicial') {
        concept = 'initial_payment';
    }

    $('#invoice_concept').val(concept);

    $('#invoice_concept_preview').text(
        $('#invoice_concept option:selected').text()
    );

    // =========================================
    // CARGAR DATOS
    // IMPORTANTE:
    // Primero cliente + empresa,
    // luego correlativo por empresa.
    // =========================================

    loadInvoiceCustomerData(saleId);

    loadInvoiceDescription(paymentId);

    $('#generateInvoiceModal').modal('show');

});


// =========================================================
// CAMBIAR CONCEPTO
// =========================================================

$(document).on('change', '#invoice_concept', function () {

    $('#invoice_concept_preview').text(
        $(this).find('option:selected').text()
    );

});


// =========================================================
// CAMBIAR TIPO DOCUMENTO
// =========================================================

$(document).on('change', '#document_type', function () {

    applyDocumentTypeUI();

    loadInvoiceCorrelative();

});


// =========================================================
// UI SEGÚN TIPO DE DOCUMENTO
// =========================================================

function applyDocumentTypeUI() {

    let type = $('#document_type').val();

    if (type === 'sale_note') {

        $('#legend').closest('.mt-3').hide();

        $('#btnGenerateInvoice').html(`
            <i class="fas fa-receipt mr-2"></i>
            Emitir Nota de Venta
        `);

    } else {

        $('#legend').closest('.mt-3').show();

        $('#btnGenerateInvoice').html(`
            <i class="fas fa-paper-plane mr-2"></i>
            Emitir Comprobante SUNAT
        `);

    }

}


// =========================================================
// CARGAR CORRELATIVO POR EMPRESA
// =========================================================

function loadInvoiceCorrelative() {

    const documentType = $('#document_type').val();

    const companyId = $('#company_id').val();

    if (!documentType) {
        $('#series').val('');
        $('#number').val('');
        return;
    }

    if (!companyId) {
        $('#series').val('');
        $('#number').val('');
        return;
    }

    $.ajax({

        url: window.routes.invoiceNextNumber,

        type: 'GET',

        data: {
            document_type: documentType,
            company_id: companyId
        },

        success: function (response) {

            $('#series').val(
                response.series
            );

            $('#number').val(
                response.number
            );

        },

        error: function () {

            $('#series').val('');
            $('#number').val('');

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo obtener el correlativo del comprobante.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });

        }

    });

}


// =========================================================
// CARGAR DATOS DEL CLIENTE Y EMPRESA
// =========================================================

function loadInvoiceCustomerData(saleId) {

    const url = window.routes.invoiceCustomerData
        .replace(':saleId', saleId);

    $.ajax({

        url: url,

        type: 'GET',

        success: function (response) {

            // =========================================
            // CLIENTE
            // =========================================

            $('#customer_name').val(
                response.data.customer_name || ''
            );

            $('#customer_document').val(
                response.data.customer_document || ''
            );

            $('#customer_address').val(
                response.data.customer_address || ''
            );

            $('#customer_department').val(
                response.data.customer_department || ''
            );

            $('#customer_province').val(
                response.data.customer_province || ''
            );

            $('#customer_district').val(
                response.data.customer_district || ''
            );

            $('#customer_ubigeo').val(
                response.data.customer_ubigeo || ''
            );

            // =========================================
            // EMPRESA EMISORA
            // IMPORTANTE:
            // ESTO DEBE IR ANTES DE loadInvoiceCorrelative()
            // =========================================

            $('#company_id').val(
                response.data.company_id || ''
            );

            $('#company_name').val(
                response.data.company_name || ''
            );

            // =========================================
            // DEFINIR BOLETA O FACTURA
            // =========================================

            const documentNumber =
                response.data.customer_document || '';

            const documentType =
                response.data.customer_document_type || '';

            if (
                documentType === 'RUC' ||
                documentNumber.length === 11
            ) {

                $('#document_type').val('invoice');

            } else {

                $('#document_type').val('receipt');

            }

            // =========================================
            // ACTUALIZAR BOTÓN / LEYENDA
            // =========================================

            applyDocumentTypeUI();

            // =========================================
            // CARGAR CORRELATIVO YA CON EMPRESA
            // =========================================

            loadInvoiceCorrelative();

        },

        error: function () {

            $('#customer_name').val('');
            $('#customer_document').val('');
            $('#customer_address').val('');
            $('#customer_department').val('');
            $('#customer_province').val('');
            $('#customer_district').val('');
            $('#customer_ubigeo').val('');
            $('#company_id').val('');
            $('#company_name').val('');
            $('#series').val('');
            $('#number').val('');

            Swal.fire({

                icon: 'warning',

                title: 'Cliente no encontrado',

                toast: true,
                timer: 2500,
                showConfirmButton: false,
                position: 'top-end'

            });

        }

    });

}


// =========================================================
// CARGAR DESCRIPCIÓN DEL COMPROBANTE
// =========================================================

function resetInvoiceMultipleLotsSummary() {
    $('#invoiceMultipleLotsCard')
        .addClass('d-none')
        .removeClass('is-expanded');
    $('#invoiceMultipleLotsMeta').text('—');
    $('#invoiceMultipleLotsCount').text('0 lotes');
    $('#invoiceMultipleLotsList').empty();
    $('#invoiceToggleLots')
        .addClass('d-none')
        .data('lot-count', 0)
        .text('Ver todos');
}

function renderInvoiceMultipleLotsSummary(summary) {
    resetInvoiceMultipleLotsSummary();

    if (!summary || !summary.is_multiple) {
        return;
    }

    const lots = Array.isArray(summary.lots) ? summary.lots : [];
    const lotCount = parseInt(summary.lot_count, 10) || lots.length;
    const saleCode = summary.sale_code || 'Venta múltiple';
    const projectName = summary.project_name || 'Proyecto';
    const $list = $('#invoiceMultipleLotsList');

    $('#invoiceMultipleLotsMeta').text(`${saleCode} · ${projectName}`);
    $('#invoiceMultipleLotsCount').text(`${lotCount} lotes`);

    lots.forEach(function (lot, index) {
        const $chip = $('<span>', {
            class: 'invoice-lot-chip' + (index >= 6 ? ' is-extra' : '')
        });

        $chip.append(
            $('<strong>').text(lot.label || 'Lote')
        );

        if (lot.code) {
            $chip.append(
                $('<small>').text(lot.code)
            );
        }

        $list.append($chip);
    });

    if (lots.length > 6) {
        $('#invoiceToggleLots')
            .removeClass('d-none')
            .data('lot-count', lotCount)
            .text(`Ver todos (${lotCount})`);
    }

    $('#invoiceMultipleLotsCard').removeClass('d-none');
}

$(document).on('click', '#invoiceToggleLots', function () {
    const $card = $('#invoiceMultipleLotsCard');
    const expanded = !$card.hasClass('is-expanded');
    const lotCount = parseInt($(this).data('lot-count'), 10) || 0;

    $card.toggleClass('is-expanded', expanded);
    $(this).text(expanded ? 'Mostrar menos' : `Ver todos (${lotCount})`);
});

function loadInvoiceDescription(paymentId) {

    const url = window.routes.invoicePaymentDescription
        .replace(':paymentId', paymentId);

    $.ajax({

        url: url,

        type: 'GET',

        success: function (response) {

            $('#description').val(
                response.data.description || ''
            );

            $('#legend').val(
                response.data.legend || ''
            );

            renderInvoiceMultipleLotsSummary(
                response.data.sale_lot_summary
            );

        },

        error: function () {

            $('#description').val('');
            $('#legend').val('');
            resetInvoiceMultipleLotsSummary();

        }

    });

}


// =========================================================
// GUARDAR / EMITIR COMPROBANTE
// =========================================================

$(document).on('click', '#btnGenerateInvoice', function () {

    const btn = $(this);

    const documentType = $('#document_type').val();

    const companyId = $('#company_id').val();

    const series = $('#series').val();

    const number = $('#number').val();

    if (!companyId) {

        Swal.fire({
            icon: 'warning',
            title: 'Empresa no detectada',
            text: 'No se pudo identificar la empresa emisora del comprobante.'
        });

        return;
    }

    if (!series || !number) {

        Swal.fire({
            icon: 'warning',
            title: 'Correlativo incompleto',
            text: 'No se pudo obtener la serie o número del comprobante.'
        });

        return;
    }

    btn.prop('disabled', true);

    if (documentType === 'sale_note') {

        btn.html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Emitiendo nota...
        `);

    } else {

        btn.html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Emitiendo SUNAT...
        `);

    }

    $.ajax({

        url: window.routes.invoiceGenerate,

        type: 'POST',

        data: $('#invoiceForm').serialize(),

        success: function (response) {

            btn.prop('disabled', false);

            applyDocumentTypeUI();

            $('#generateInvoiceModal').modal('hide');

            // =========================================
            // PDF A4
            // =========================================

            if (response.pdf_url) {

                window.open(
                    response.pdf_url,
                    '_blank'
                );

            }

            // =========================================
            // TICKET
            // =========================================

            if (response.ticket_url) {

                setTimeout(function () {

                    window.open(
                        response.ticket_url,
                        '_blank',
                        'width=420,height=800'
                    );

                }, 500);

            }

            Swal.fire({

                icon: 'success',

                title: response.message || 'Comprobante emitido correctamente.',

                toast: true,

                position: 'top-end',

                showConfirmButton: false,

                timer: 3000

            });

            tablePayment.ajax.reload(null, false);

        },

        error: function (xhr) {

            btn.prop('disabled', false);

            applyDocumentTypeUI();

            Swal.fire({

                icon: 'error',

                title: 'Error al emitir',

                text: xhr.responseJSON?.message ||
                    'No se pudo emitir el comprobante.'

            });

        }

    });

});


// =========================================================
// LIMPIAR MODAL FACTURACIÓN
// =========================================================

$('#generateInvoiceModal').on('hidden.bs.modal', function () {

    $('#invoiceForm')[0].reset();

    $('#company_id').val('');
    $('#company_name').val('');
    $('#series').val('');
    $('#number').val('');
    $('#description').val('');
    $('#legend').val('');
    resetInvoiceMultipleLotsSummary();

    $('#subtotal_preview').text('S/ 0.00');
    $('#igv_preview').text('S/ 0.00');
    $('#total_preview').text('S/ 0.00');
    $('#invoice_total_preview').text('S/ 0.00');
    $('#invoice_concept_preview').text('—');

    $('#btnGenerateInvoice')
        .prop('disabled', false)
        .html(`
            <i class="fas fa-paper-plane mr-2"></i>
            Emitir Comprobante SUNAT
        `);

});
