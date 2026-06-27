var divLoading = document.getElementById('divLoading');

let tablePayment;

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

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
        placeholder: 'Seleccione una venta'
    });

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

                    $('.is-invalid').removeClass('is-invalid');

                    $('.invalid-feedback').text('');

                    $.each(errors, function (key, messages) {

                        const input = $(`#${key}`);

                        input.addClass('is-invalid');

                        $(`#${key}-error`).text(messages[0]);

                    });

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

        $('#bank_id').val('');
        $('#operation_number').val('');

    });

    // =========================================================
    // ELIMINAR
    // =========================================================

    // =========================================================
    // ANULAR PAGO
    // =========================================================

    $(document).on('click', '.cancelPayment', function () {

        const id = $(this).data('id');

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

                $.ajax({

                    url: `/admin/payments/${id}/cancel`,

                    type: 'POST',

                    success: function (response) {

                        tablePayment.ajax.reload(null, false);

                        Swal.fire({

                            icon: 'success',

                            title: response.message,

                            toast: true,

                            position: 'top-end',

                            showConfirmButton: false,

                            timer: 3000

                        });

                    },

                    error: function (xhr) {

                        Swal.fire({

                            icon: 'error',

                            title: 'Error',

                            text: xhr.responseJSON?.message ||
                                'Error al anular pago'

                        });

                    }

                });

            }

        });

    });

    // =========================================================
    // VER DETALLE
    // =========================================================

    $(document).on('click', '.viewPayment', function () {

        const status = $(this).data('status');

        let badgeClass = 'badge-secondary';

        if (status === 'activo') {
            badgeClass = 'badge-success';
        }

        if (status === 'anulado') {
            badgeClass = 'badge-danger';
        }

        $('#vp_sale').text(
            $(this).data('sale') || '—'
        );

        $('#vp_schedule').text(
            $(this).data('installment') || '—'
        );

        $('#vp_payment_type').text(
            $(this).data('payment_type') || '—'
        );

        $('#vp_payment_date').text(
            $(this).data('payment_date') || '—'
        );

        $('#vp_amount').text(
            'S/ ' + parseFloat($(this).data('amount') || 0).toFixed(2)
        );

        $('#vp_late_fee').text(
            'S/ ' + parseFloat($(this).data('late_fee_paid') || 0).toFixed(2)
        );

        $('#vp_discount').text(
            'S/ ' + parseFloat($(this).data('discount') || 0).toFixed(2)
        );

        $('#vp_payment_method').text(
            $(this).data('payment_method') || '—'
        );

        $('#vp_operation_number').text(
            $(this).data('operation_number') || '—'
        );

        $('#vp_observation').text(
            $(this).data('observation') || '—'
        );

        $('#vp_status')
            .removeClass('badge-success badge-danger badge-secondary')
            .addClass(badgeClass)
            .text(status ? status.toUpperCase() : '—');

        $('#vp_created_by').text(
            $(this).data('created_by') || '—'
        );

        $('#vp_updated_by').text(
            $(this).data('updated_by') || '—'
        );

        $('#vp_created_at').text(
            $(this).data('created_at') || '—'
        );

        $('#vp_updated_at').text(
            $(this).data('updated_at') || '—'
        );

        $('#viewPaymentModal').modal('show');

        $('#vp_id').text(
            $(this).data('id') || '—'
        );

        $('#vp_created_by_user').text(
            $(this).data('created_by') || '—'
        );

        $('#vp_updated_by_user').text(
            $(this).data('updated_by') || '—'
        );

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

                const totalReal =
                    parseFloat(schedule.total_amount || 0) +
                    parseFloat(schedule.late_fee || 0);

                options += `
                    <option 
                        value="${schedule.id}"
                        data-amount="${schedule.total_amount}"
                        data-late_fee="${schedule.late_fee}"
                    >
                        Cuota #${schedule.installment_number}
                        - Vence: ${schedule.due_date}
                        - Total: S/ ${totalReal.toFixed(2)}
                        - Mora: S/ ${parseFloat(schedule.late_fee || 0).toFixed(2)}
                        - Saldo: S/ ${parseFloat(schedule.remaining_balance || 0).toFixed(2)}
                    </option>
                `;
            });

            $('#payment_schedule_id').html(options);

            $('#payment_schedule_id').off('change.autoLateFee');

            $('#payment_schedule_id').on('change.autoLateFee', function () {

                const selected = $(this).find(':selected');

                const lateFee = parseFloat(selected.data('late_fee') || 0);

                $('#late_fee_paid').val(lateFee.toFixed(2));

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

    const installment = selected.text();

    const balance = parseFloat(
        selected.data('amount') || 0
    );

    const lateFee = parseFloat(
        selected.data('late_fee') || 0
    );

    const row = `
        <tr data-schedule-id="${scheduleId}">

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

                <input type="number"
                    step="0.01"
                    min="0"
                    class="form-control form-control-sm applied-amount"
                    name="payment_details[${scheduleId}][applied_amount]"
                    value="${balance.toFixed(2)}">

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

    recalculateTotalPayment();

});

// =========================================================
// RECALCULAR TOTAL
// =========================================================

$(document).on('keyup change', '.applied-amount', function () {

    recalculateTotalPayment();

});

function togglePaymentFields() {

    let method = $('#payment_method').val();

    if (
        method === 'transferencia' ||
        method === 'deposito'
    ) {

        $('#bank_container').show();
        $('#operation_container').show();

    } else {

        $('#bank_container').hide();
        $('#operation_container').hide();

        $('#bank_id').val('');
        $('#operation_number').val('');

    }
}

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

        },

        error: function () {

            $('#description').val('');
            $('#legend').val('');

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
