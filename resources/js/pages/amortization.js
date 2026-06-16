
let tableAmortization;

$(function () {

    $('[data-toggle="tooltip"]').tooltip();

});

// =========================================================
// DATATABLE
// =========================================================

document.addEventListener('DOMContentLoaded', function () {

    tableAmortization = $('#tableAmortization').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.amortizationList,

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
                data: 'payment',
                name: 'payment'
            },

            {
                data: 'date',
                name: 'date'
            },

            {
                data: 'amount',
                name: 'amount'
            },

            {
                data: 'recalculation_type',
                name: 'recalculation_type'
            },

            {
                data: 'reduced_installments',
                name: 'reduced_installments'
            },

            {
                data: 'new_installment',
                name: 'new_installment'
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

        ]

    });

});

// =========================================================
// LIMPIAR MODAL
// =========================================================

$('#amortizationModal').on('hidden.bs.modal', function () {

    $('#payment_method').val('efectivo');

    $('#operationNumberBox').hide();

    $('#operation_number').val('');

    $('#amortizationForm')[0].reset();

    $('#amortizationForm').removeAttr('data-id');

    $('.invalid-feedback').text('');

    $('.is-invalid').removeClass('is-invalid');

});


// =========================================================
// CONTROLAR MÉTODO DE PAGO
// =========================================================

// =========================================================
// CONTROLAR MÉTODO DE PAGO
// =========================================================

function toggleBankFields() {

    const metodo = $('#payment_method').val();

    // ============================================
    // TRANSFERENCIA O DEPÓSITO
    // ============================================

    if (
        metodo === 'transferencia' ||
        metodo === 'deposito'
    ) {

        $('#bankBox').removeClass('d-none');

        $('#operationNumberBox').removeClass('d-none');

    }

    // ============================================
    // EFECTIVO / YAPE / PLIN
    // ============================================

    else {

        $('#bankBox').addClass('d-none');

        $('#operationNumberBox').addClass('d-none');

        $('#bank_id').val('');

        $('#operation_number').val('');
    }
}

// =========================================================
// CAMBIO MÉTODO DE PAGO
// =========================================================

$(document).on(
    'change',
    '#payment_method',
    function () {

        toggleBankFields();

    }
);

// =========================================================
// AL ABRIR MODAL
// =========================================================

$('#amortizationModal').on(
    'shown.bs.modal',
    function () {

        toggleBankFields();

    }
);

// =========================================================
// LIMPIAR MODAL
// =========================================================

$('#amortizationModal').on(
    'hidden.bs.modal',
    function () {

        $('#payment_method').val('efectivo');

        $('#bank_id').val('');

        $('#operation_number').val('');

        $('#bankBox').addClass('d-none');

        $('#operationNumberBox').addClass('d-none');

    }
);
// =========================================================
// CAMBIO TIPO RECÁLCULO
// =========================================================

$(document).on('change', '#recalculation_type', function () {

    const type = $(this).val();

    if (
        type === 'reducir_cuota'
    ) {

        $('#box_new_installment')
            .removeClass('d-none');

        $('#box_reduced_installments')
            .addClass('d-none');

    }
    else if (
        type === 'reducir_tiempo'
    ) {

        $('#box_new_installment')
            .addClass('d-none');

        $('#box_reduced_installments')
            .removeClass('d-none');

    }
    else if (
        type === 'descuento'
    ) {

        // descuento usa lógica de reducir tiempo

        $('#box_new_installment')
            .addClass('d-none');

        $('#box_reduced_installments')
            .removeClass('d-none');

    }
    else {

        $('#box_new_installment')
            .addClass('d-none');

        $('#box_reduced_installments')
            .addClass('d-none');

    }

});

calcularReduccionTiempo();

// =========================================================
// RECALCULAR AL ESCRIBIR MONTO
// =========================================================

$(document).on(
    'keyup change',
    '#amount',
    function () {

        calcularReduccionTiempo();

    }
);

// =========================================================
// RECALCULAR AL CAMBIAR VENTA
// =========================================================

$(document).on(
    'change',
    '#sale_id',
    function () {

        calcularReduccionTiempo();

    }
);

$(document).on(
    'change',
    '#recalculation_type',
    function () {

        calcularReduccionTiempo();

    }
);

// =========================================================
// CALCULAR REDUCCIÓN AUTOMÁTICA
// =========================================================
function calcularReduccionTiempo() {

    const monto =
        parseFloat($('#amount').val()) || 0;

    const descuento =
        parseFloat($('#discount_amount').val()) || 0;

    const tipo =
        $('#recalculation_type').val();


    // ========================================
    // MOSTRAR / OCULTAR CAMPOS
    // ========================================

    if (tipo == 'reducir_tiempo') {

        // mostrar cuotas reducidas
        $('#box_reduced_installments')
            .removeClass('d-none');

        // ocultar nueva cuota
        $('#box_new_installment')
            .addClass('d-none');

    } else if (tipo == 'reducir_cuota') {

        // mostrar nueva cuota
        $('#box_new_installment')
            .removeClass('d-none');

        // ocultar cuotas reducidas
        $('#box_reduced_installments')
            .addClass('d-none');
    }

    // ========================================
    // REDUCIR CUOTA
    // ========================================
    // ========================================
    // REDUCIR CUOTA
    // ========================================

    if (tipo == 'reducir_cuota') {

        const selected =
            $('#sale_id option:selected');

        // cuotas totales
        const cuotasTotales =
            Number(selected.attr('data-installments')) || 0;

        // cuotas pagadas
        const cuotasPagadas =
            Number(selected.attr('data-paid')) || 0;

        // cuota mensual actual
        const cuotaMensual =
            Number(selected.attr('data-monthly')) || 0;

        // cuotas pendientes reales
        const cuotasPendientes =
            cuotasTotales - cuotasPagadas;

        // deuda actual
        const deudaActual =
            cuotasPendientes * cuotaMensual;

        // nuevo saldo
        let nuevoSaldo =
            deudaActual - monto - descuento;

        if (nuevoSaldo < 0) {

            nuevoSaldo = 0;
        }

        // nueva cuota
        let nuevaCuota =
            nuevoSaldo / cuotasPendientes;

        // mostrar
        $('#current_installments')
            .text(cuotasPendientes);

        $('#new_installments')
            .text(cuotasPendientes);

        $('#saved_installments')
            .text(0);

        $('#new_installment')
            .val(nuevaCuota.toFixed(2));

        return;
    }



    if (
        tipo !== 'reducir_tiempo'
        &&
        tipo !== 'descuento'
    ) {

        return;
    }

    const selected =
        $('#sale_id option:selected');

    // =========================================
    // DATOS
    // =========================================

    const cuotasTotales =
        Number(selected.attr('data-installments')) || 0;

    const cuotasPagadas =
        Number(selected.attr('data-paid')) || 0;

    const cuotaMensual =
        Number(selected.attr('data-monthly')) || 0;

    // =========================================
    // CUOTAS PENDIENTES REALES
    // =========================================

    const cuotasPendientes =
        cuotasTotales - cuotasPagadas;

    // =========================================
    // DEUDA REAL
    // =========================================

    const deudaActual =
        cuotasPendientes * cuotaMensual;

    // =========================================
    // NUEVO SALDO
    // =========================================

    let nuevoSaldo =
        deudaActual - monto - descuento;

    if (nuevoSaldo < 0) {

        nuevoSaldo = 0;
    }

    // =========================================
    // NUEVAS CUOTAS
    // =========================================

    const nuevasCuotas =
        Math.ceil(
            nuevoSaldo / cuotaMensual
        );

    // =========================================
    // CUOTAS REDUCIDAS
    // =========================================

    const cuotasReducidas =
        cuotasPendientes - nuevasCuotas;

    // =========================================
    // MOSTRAR
    // =========================================

    $('#current_installments')
        .text(cuotasPendientes);

    $('#new_installments')
        .text(nuevasCuotas);

    $('#saved_installments')
        .text(cuotasReducidas);

    $('#reduced_installments')
        .val(cuotasReducidas);
}
// =========================================================
// GUARDAR AMORTIZACIÓN
// =========================================================

$('#amortizationForm').on('submit', function (e) {

    e.preventDefault();

    const btn = $('#btnSaveAmortization');

    btn.prop('disabled', true);

    btn.html(`
        <span class="spinner-border spinner-border-sm mr-1"></span>
        Guardando...
    `);

    const formData = new FormData(this);

    $.ajax({

        url: window.routes.storeAmortization,

        type: 'POST',

        data: formData,

        processData: false,

        contentType: false,

        success: function (response) {

            btn.prop('disabled', false);

            btn.html(`
                <i class="fas fa-save mr-1"></i>
                Guardar Amortización
            `);

            $('#amortizationModal').modal('hide');

            tableAmortization.ajax.reload(null, false);

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

            btn.prop('disabled', false);

            btn.html(`
                <i class="fas fa-save mr-1"></i>
                Guardar Amortización
            `);

            $('.invalid-feedback').text('');

            $('.is-invalid').removeClass('is-invalid');

            if (xhr.status === 422) {

                const errors = xhr.responseJSON.errors;

                $.each(errors, function (key, value) {

                    $(`#${key}`)
                        .addClass('is-invalid');

                    $(`#${key}-error`)
                        .text(value[0]);

                });

            } else {

                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text: xhr.responseJSON?.message ||
                        'Error al registrar amortización'

                });

            }

        }

    });

    // =========================================================
    // MÉTODO DE PAGO
    // =========================================================


});

$(document).on('change', '#payment_method', function () {

    let metodo = $(this).val();

    if (metodo === 'efectivo') {

        $('#operationNumberBox').hide();

        $('#operation_number').val('');

    } else {

        $('#operationNumberBox').show();

    }

});