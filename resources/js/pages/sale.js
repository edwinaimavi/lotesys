var divLoading = document.getElementById('divLoading');

let tableSale;

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

document.addEventListener("DOMContentLoaded", function () {

    // =========================================================
    // CSRF TOKEN
    // =========================================================

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // =========================================================
    // SELECT2
    // =========================================================

    function initSaleSelect2() {

        if (typeof $.fn.select2 === 'undefined') {
            return;
        }

        const selects = [
            {
                selector: '#customer_id',
                placeholder: 'Seleccione un cliente'
            },
            {
                selector: '#lot_id',
                placeholder: 'Seleccione un lote'
            }
        ];

        selects.forEach(function (item) {

            const $select = $(item.selector);

            if (!$select.length) {
                return;
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                dropdownParent: $('#saleModal'),
                placeholder: item.placeholder
            });

        });

    }

    initSaleSelect2();

    // =========================================================
    // GENERAR CÓDIGO AUTOMÁTICO
    // =========================================================

    function generateSaleCode() {

        $.ajax({

            url: window.routes.generateSaleCode,

            type: 'GET',

            success: function (response) {

                $('#sale_code').val(response.code);

            },

            error: function () {

                console.error('Error generating sale code');

            }

        });

    }

    // =========================================================
    // CALCULAR SALDO
    // =========================================================

    function calculateBalance() {

        let lotPrice = parseFloat($('#lot_price').val()) || 0;

        let initialPayment = parseFloat($('#initial_payment').val()) || 0;

        let balance = lotPrice - initialPayment;

        if (balance < 0) {
            balance = 0;
        }

        $('#balance_finance').val(balance.toFixed(2));

    }

    // =========================================================
    // CALCULAR CUOTA
    // =========================================================

    function calculateMonthlyPayment() {

        let mode = $('#payment_mode').val();

        let balance = parseFloat($('#balance_finance').val()) || 0;

        let installments = parseInt($('#installments_count').val()) || 0;

        // ==========================================
        // AUTOMÁTICO
        // ==========================================

        if (mode === 'automatico') {

            if (balance > 0 && installments > 0) {

                let monthly = balance / installments;

                $('#monthly_payment').val(
                    monthly.toFixed(2)
                );
            }

        }

        // ==========================================
        // PERSONALIZADO
        // ==========================================

        if (mode === 'personalizado') {

            let customPayment =
                parseFloat($('#custom_payment').val()) || 0;

            $('#monthly_payment').val(
                customPayment.toFixed(2)
            );

        }

    }

    // =========================================================
    // EVENTOS CÁLCULO
    // =========================================================

    $('#lot_price, #initial_payment').on('keyup change', function () {

        calculateBalance();

        calculateMonthlyPayment();

    });

    $('#installments_count').on('keyup change', function () {

        calculateMonthlyPayment();

    });

    // ==========================================
    // CAMBIAR MODO
    // ==========================================

    $('#payment_mode').on('change', function () {

        const mode = $(this).val();

        if (mode === 'personalizado') {

            $('#custom_payment_container')
                .removeClass('d-none');

            $('#customPaymentAlert')
                .removeClass('d-none');

            $('#monthly_payment_help').html(
                'Monto personalizado'
            );

        } else {

            $('#custom_payment_container')
                .addClass('d-none');

            $('#customPaymentAlert')
                .addClass('d-none');

            $('#monthly_payment_help').html(
                'Calculado automáticamente'
            );

        }

        calculateMonthlyPayment();

    });

    // ==========================================
    // CAMBIAR CUOTA PERSONALIZADA
    // ==========================================

    $('#custom_payment').on(
        'keyup change',
        function () {

            calculateMonthlyPayment();

        }
    );

    // =========================================================
    // VENTA HISTÓRICA / REGULARIZACIÓN
    // =========================================================

    function toggleLegacySaleFields() {

        const isLegacy = $('#is_legacy_sale').is(':checked');

        if (isLegacy) {

            $('#legacy_sale_fields')
                .removeClass('d-none');

            $('#collection_rules_start_date, #legacy_observation')
                .prop('disabled', false);

        } else {

            $('#legacy_sale_fields')
                .addClass('d-none');

            $('#collection_rules_start_date, #legacy_observation')
                .prop('disabled', true)
                .val('');

        }

    }

    $('#is_legacy_sale').on('change', function () {

        toggleLegacySaleFields();

    });

    // =========================================================
    // ABRIR MODAL NUEVA VENTA
    // =========================================================

    $('#saleModal').on('show.bs.modal', function () {

        const id = $('#saleForm').attr('data-id');

        if (!id) {

            generateSaleCode();

        }

        initSaleSelect2();

    });

    // =========================================================
    // GUARDAR / ACTUALIZAR
    // =========================================================

    $('#saleForm').on('submit', function (e) {

        e.preventDefault();

        // =====================================================
        // EVITAR DOBLE CLIC
        // =====================================================

        const btn = $('#btnSaveSale');

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

            url = "/admin/sales/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeSale;

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
                    Guardar Venta
                `);

                $('#saleModal').modal('hide');

                tableSale.ajax.reload(null, false);

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
                    Guardar Venta
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

                    console.error(xhr);

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
    // EDITAR VENTA
    // =========================================================

    $(document).on('click', '.editSale', function () {

        const id = $(this).data('id');

        $('#saleForm').attr('data-id', id);

        $('#sale_code').val($(this).data('sale_code'));

        $('#customer_id').val($(this).data('customer_id'));

        $('#lot_id').val($(this).data('lot_id'));

        $('#sale_type').val($(this).data('sale_type'));

        applySaleTypeLogic();

        $('#sale_date').val($(this).data('sale_date'));

        $('#lot_price').val($(this).data('lot_price'));

        $('#initial_payment').val($(this).data('initial_payment'));

        $('#balance_finance').val($(this).data('balance_finance'));

        $('#installments_count').val($(this).data('installments_count'));

        $('#monthly_payment').val($(this).data('monthly_payment'));

        $('#interest_rate').val($(this).data('interest_rate'));

        $('#first_payment_date').val($(this).data('first_payment_date'));

        $('#payment_day').val($(this).data('payment_day'));

        $('#late_fee_setting_id').val(
            $(this).data('late_fee_setting_id')
        );

        const isLegacySale =
            parseInt($(this).data('is_legacy_sale')) === 1;

        $('#is_legacy_sale')
            .prop('checked', isLegacySale);

        toggleLegacySaleFields();

        $('#collection_rules_start_date').val(
            $(this).data('collection_rules_start_date') || ''
        );

        $('#legacy_observation').val(
            $(this).data('legacy_observation') || ''
        );

        $('#status').val($(this).data('status'));

        $('.icon_modal').html(`
            <i class="far fa-edit text-primary"></i>
        `);

        $('#saleModalLabel').html('EDITAR VENTA');

        $('#saleModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#saleModal').on('hidden.bs.modal', function () {

        const $form = $('#saleForm');

        $form[0].reset();

        $('#payment_mode').val('automatico');

        $('#custom_payment').val('');

        $('#custom_payment_container')
            .addClass('d-none');

        $('#customPaymentAlert')
            .addClass('d-none');

        $('#monthly_payment_help').html(
            'Calculado automáticamente'
        );

        $('#is_legacy_sale')
            .prop('checked', false);

        toggleLegacySaleFields();

        $form.removeAttr('data-id');

        $('#saleModalLabel').html('NUEVA VENTA');

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });

    // =========================================================
    // DATATABLE
    // =========================================================

    tableSale = $('#tableSale').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.saleList,

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
                data: 'sale_code',
                name: 'sale_code'
            },

            {
                data: 'customer',
                name: 'customer'
            },

            {
                data: 'lot',
                name: 'lot'
            },

            {
                data: 'sale_date',
                name: 'sale_date'
            },

            {
                data: 'lot_price',
                name: 'lot_price'
            },

            {
                data: 'initial_payment',
                name: 'initial_payment'
            },

            {
                data: 'balance_finance',
                name: 'balance_finance'
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
    // ELIMINAR VENTA
    // =========================================================

    $(document).on('click', '.deleteSale', function () {

        const id = $(this).data('id');

        Swal.fire({

            title: '¿Está seguro?',

            text: 'Esta acción no podrá revertirse.',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonText: 'Sí, eliminar',

            cancelButtonText: 'Cancelar'

        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    url: `${window.routes.deleteSale}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableSale.ajax.reload(null, false);

                        Swal.fire({

                            icon: 'success',

                            title: response.message,

                            toast: true,

                            position: 'top-end',

                            showConfirmButton: false,

                            timer: 3000

                        });

                    },

                    error: function () {

                        Swal.fire({

                            icon: 'error',

                            title: 'Error',

                            text: 'Ocurrió un error al eliminar.'

                        });

                    }

                });

            }

        });

    });


    // =========================================================
    // VER DETALLE VENTA
    // =========================================================

    $(document).on('click', '.viewSale', function () {

        const status = $(this).data('status');

        let badgeClass = 'badge-secondary';

        if (status === 'activo') {
            badgeClass = 'badge-success';
        }

        if (status === 'cancelado') {
            badgeClass = 'badge-danger';
        }

        if (status === 'rescindido') {
            badgeClass = 'badge-warning';
        }

        if (status === 'finalizado') {
            badgeClass = 'badge-primary';
        }

        // =========================================
        // PANEL IZQUIERDO
        // =========================================

        $('#vs_codigo_venta').text(
            $(this).data('sale_code') || '—'
        );

        $('#vs_cliente').text(
            $(this).data('customer') || '—'
        );

        $('#vs_created_by').text(
            $(this).data('created_by') || '—'
        );

        $('#vs_updated_at').text(
            $(this).data('updated_at') || '—'
        );

        $('#vs_estado_badge')
            .removeClass('badge-success badge-danger badge-warning badge-primary badge-secondary')
            .addClass(badgeClass)
            .text(status ? status.toUpperCase() : '—');

        // =========================================
        // INFORMACIÓN GENERAL
        // =========================================

        $('#vs_cliente_nombre').text(
            $(this).data('customer') || '—'
        );

        $('#vs_lote').text(
            $(this).data('lot') || '—'
        );

        $('#vs_fecha_venta').text(
            $(this).data('sale_date') || '—'
        );

        // =========================================
        // FINANCIERA
        // =========================================

        $('#vs_precio_lote').text(
            'S/ ' + parseFloat($(this).data('lot_price') || 0).toFixed(2)
        );

        $('#vs_inicial').text(
            'S/ ' + parseFloat($(this).data('initial_payment') || 0).toFixed(2)
        );

        $('#vs_saldo_financiar').text(
            'S/ ' + parseFloat($(this).data('balance_finance') || 0).toFixed(2)
        );

        // =========================================
        // FINANCIAMIENTO
        // =========================================

        $('#vs_cantidad_cuotas').text(
            $(this).data('installments_count') || '—'
        );

        $('#vs_cuota_mensual').text(
            'S/ ' + parseFloat($(this).data('monthly_payment') || 0).toFixed(2)
        );

        $('#vs_tasa_interes').text(
            ($(this).data('interest_rate') || 0) + ' %'
        );

        $('#vs_fecha_primer_pago').text(
            $(this).data('first_payment_date') || '—'
        );

        $('#vs_dia_pago').text(
            $(this).data('payment_day') || '—'
        );

        $('#vs_estado_text').text(
            status || '—'
        );

        // =========================================
        // SISTEMA
        // =========================================

        $('#vs_id').text(
            $(this).data('id') || '—'
        );

        $('#vs_created_at').text(
            $(this).data('created_at') || '—'
        );

        $('#vs_created_by_user').text(
            $(this).data('created_by') || '—'
        );

        $('#vs_updated_by_user').text(
            $(this).data('updated_by') || '—'
        );

        // =========================================
        // ABRIR MODAL
        // =========================================

        $('#viewSaleModal').modal('show');

    });

    // =========================================================
    // AUTOCOMPLETAR SEGÚN TIPO DE VENTA
    // =========================================================

    function applySaleTypeLogic() {

        const saleType = $('#sale_type').val();

        const option = $('#lot_id').find(':selected');

        const cashPrice = parseFloat(
            option.data('cash_price')
        ) || 0;

        const financedPrice = parseFloat(
            option.data('financed_price')
        ) || 0;

        // =====================================================
        // CONTADO
        // =====================================================

        if (saleType === 'contado') {

            // =========================================
            // PRECIO CONTADO
            // =========================================

            $('#lot_price').val(
                cashPrice.toFixed(2)
            );

            // =========================================
            // INICIAL
            // =========================================

            $('#initial_payment').val(0);

            // =========================================
            // SALDO
            // =========================================

            $('#balance_finance').val(
                cashPrice.toFixed(2)
            );

            // =========================================
            // CUOTAS
            // =========================================

            $('#installments_count').val(1);

            // =========================================
            // CUOTA
            // =========================================

            $('#monthly_payment').val(
                cashPrice.toFixed(2)
            );

            // =========================================
            // INTERÉS
            // =========================================

            $('#interest_rate').val(0);

            // =========================================
            // FECHA PRIMER PAGO
            // =========================================

            $('#first_payment_date').val(
                $('#sale_date').val()
            );

            // =========================================
            // DÍA PAGO
            // =========================================

            let fechaVenta = $('#sale_date').val();

            if (fechaVenta) {

                let partes = fechaVenta.split('-');

                $('#payment_day').val(partes[2]);

            }

            // =========================================
            // BLOQUEAR CAMPOS
            // =========================================

            $('#initial_payment').prop('readonly', true);

            $('#installments_count').prop('readonly', true);

            $('#monthly_payment').prop('readonly', true);

            $('#interest_rate').prop('readonly', true);

            $('#payment_day').prop('readonly', true);

            $('#first_payment_date').prop('readonly', true);

        }
        // =====================================================
        // FINANCIADO
        // =====================================================

        else if (saleType === 'financiado') {

            // =========================================
            // PRECIO FINANCIADO
            // =========================================
            $('#lot_price').val(
                financedPrice.toFixed(2)
            );

            // =========================================
            // RECALCULAR SALDO
            // =========================================
            calculateBalance();

            // =========================================
            // INTERÉS POR DEFECTO
            // (No manejamos interés)
            // =========================================
            $('#interest_rate').val(0);

            // =========================================
            // SI NO HAY CUOTAS, DEJAR VACÍO
            // =========================================
            $('#installments_count').val('');

            // =========================================
            // LIMPIAR CUOTA MENSUAL
            // =========================================
            $('#monthly_payment').val('');

            // =========================================
            // PRIMER PAGO = FECHA DE VENTA
            // =========================================
            const fechaVenta = $('#sale_date').val();

            if (fechaVenta) {
                $('#first_payment_date').val(fechaVenta);

                // Día de pago = día de la fecha de venta
                const partes = fechaVenta.split('-');
                $('#payment_day').val(parseInt(partes[2]));
            }

            // =========================================
            // HABILITAR CAMPOS
            // =========================================
            $('#initial_payment').prop('readonly', false);
            $('#installments_count').prop('readonly', false);
            $('#monthly_payment').prop('readonly', true);
            $('#interest_rate').prop('readonly', false);
            $('#payment_day').prop('readonly', false);
            $('#first_payment_date').prop('readonly', false);
        }

        // =====================================================
        // SIN TIPO
        // =====================================================

        else {

            $('#lot_price').val('');

            $('#installments_count').val('');

            $('#monthly_payment').val('');

            $('#interest_rate').val('');

            $('#balance_finance').val('');

        }

    }

    // =========================================================
    // CAMBIAR LOTE
    // =========================================================

    $('#lot_id').on('change', function () {

        applySaleTypeLogic();

    });

    // =========================================================
    // CAMBIAR TIPO VENTA
    // =========================================================

    $('#sale_type').on('change', function () {

        applySaleTypeLogic();

    });


    // =========================================================
    // CAMBIAR FECHA DE VENTA
    // =========================================================
    $('#sale_date').on('change', function () {

        if ($('#sale_type').val() === 'financiado') {

            const fechaVenta = $(this).val();

            if (fechaVenta) {
                $('#first_payment_date').val(fechaVenta);

                const partes = fechaVenta.split('-');
                $('#payment_day').val(parseInt(partes[2]));
            }
        }

    });
    // =========================================================
    // VER CRONOGRAMA
    // =========================================================

    // =========================================================
    // VER CRONOGRAMA
    // =========================================================

    // =========================================================
    // VER CRONOGRAMA PRO
    // =========================================================

    $(document).on('click', '.viewSchedule', function () {

        const saleId = $(this).data('id');

        // =====================================================
        // DATOS GENERALES
        // =====================================================

        $('#ps_sale_code').text(
            $(this).data('sale_code') || '—'
        );

        $('#ps_customer').text(
            $(this).data('customer') || '—'
        );

        $('#ps_lot').text(
            $(this).data('lot') || '—'
        );

        // =====================================================
        // LOADING
        // =====================================================

        $('#paymentScheduleBody').html(`
        <tr>
            <td colspan="9" class="text-center py-5">
                <div class="spinner-border text-primary mb-2"></div>
                <div>Cargando cronograma...</div>
            </td>
        </tr>
    `);

        // =====================================================
        // RESETEAR CARDS
        // =====================================================

        $('#ps_financed').text('S/ 0.00');
        $('#ps_paid').text('S/ 0.00');
        $('#ps_pending').text('S/ 0.00');

        $('#ps_progress_bar').css('width', '0%');
        $('#ps_progress_text').text('0%');

        // =====================================================
        // ABRIR MODAL
        // =====================================================

        $('#paymentScheduleModal').modal('show');

        // =====================================================
        // CONSULTAR
        // =====================================================

        $.ajax({

            url: `${window.routes.paymentSchedule}/${saleId}/payment-schedule`,

            type: 'GET',

            success: function (response) {

                const sale = response.sale;

                const schedules = response.schedules;

                const history = response.history || [];

                let html = '';

                // =================================================
                // TOTALES
                // =================================================

                // =================================================
                // TOTALES
                // =================================================

                let financiado = parseFloat(sale.lot_price || 0);

                let totalPagado = 0;
                let totalMora = 0;

                // =========================================
                // SUMAR CUOTAS PAGADAS
                // =========================================

                schedules.forEach(item => {

                    const cuota = parseFloat(
                        item.total_amount || 0
                    );

                    const mora = parseFloat(
                        item.late_fee || 0
                    );

                    if (item.status === 'pagado') {

                        totalPagado += cuota + mora;

                        totalMora += mora;

                    }

                });

                // =========================================
                // SUMAR AMORTIZACIONES
                // =========================================

                const amortizaciones = parseFloat(
                    response.total_amortizado || 0
                );
                totalPagado += amortizaciones;


                const descuentos = parseFloat(
                    response.total_descuentos || 0
                );

                totalPagado += descuentos;


                // =========================================
                // DEUDA ACTUAL
                // =========================================

                let deudaActual = financiado - totalPagado;

                // =========================================
                // FINANCIAMIENTO REAL
                // =========================================

                const financiamientoReal =
                    financiado - descuentos;

                if (deudaActual < 0) {
                    deudaActual = 0;
                }

                // =========================================
                // RESUMEN FINANCIERO
                // =========================================

                $('#rf_original').text(
                    'S/ ' + financiado.toFixed(2)
                );

                $('#rf_amortizado').text(
                    'S/ ' + amortizaciones.toFixed(2)
                );

                $('#rf_mora').text(
                    'S/ ' + totalMora.toFixed(2)
                );

                $('#rf_descuentos').text(
                    'S/ ' + descuentos.toFixed(2)
                );

                $('#rf_real').text(
                    'S/ ' + financiamientoReal.toFixed(2)
                );

                $('#rf_pagado').text(
                    'S/ ' + totalPagado.toFixed(2)
                );

                $('#rf_deuda').text(
                    'S/ ' + deudaActual.toFixed(2)
                );

                // =========================================
                // HISTORIAL FINANCIERO
                // =========================================

                let historyHtml = '';

                if (history.length > 0) {

                    history.forEach(item => {

                        let operacion = '';
                        let resultado = '';

                        if (item.payment_type === 'inicial') {

                            operacion = 'Cuota Inicial';
                            resultado = 'Pago registrado';

                        } else if (item.payment_type === 'cuota') {

                            operacion = 'Pago Cuota';
                            resultado = 'Cuota pagada';

                        } else if (item.payment_type === 'amortizacion') {

                            if (item.discount_amount > 0) {

                                operacion = 'Descuento';
                                resultado = 'Descuento aplicado: S/ ' + parseFloat(item.discount_amount).toFixed(2);

                            } else if (item.recalculation_type === 'reducir_tiempo') {

                                operacion = 'Amortización';
                                resultado = 'Reducción: ' + item.reduced_installments + ' cuotas';

                            } else if (item.recalculation_type === 'reducir_cuota') {

                                operacion = 'Reducción Cuota';
                                resultado = 'Nueva cuota: S/ ' + parseFloat(item.new_installment || 0).toFixed(2);

                            } else {

                                operacion = 'Amortización';
                                resultado = 'Recalculo financiero';

                            }

                        }

                        historyHtml += `
            <tr>
                <td>${item.date}</td>
                <td>
                    <span class="badge badge-light px-3 py-2">
                        ${operacion}
                    </span>
                </td>
                <td class="font-weight-bold text-success">
                    S/ ${parseFloat(item.amount || 0).toFixed(2)}
                </td>
                <td>${resultado}</td>
            </tr>
        `;
                    });

                } else {

                    historyHtml = `
        <tr>
            <td colspan="4" class="py-4 text-muted">
                No hay movimientos financieros
            </td>
        </tr>
    `;
                }

                $('#financialHistoryBody').html(historyHtml);

                // =========================================
                // PORCENTAJE
                // =========================================


                // =================================================
                // TABLA
                // =================================================

                if (schedules.length === 0) {

                    html = `
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            No existen cuotas generadas.
                        </td>
                    </tr>
                `;

                } else {

                    schedules.forEach((item, index) => {

                        const capital = parseFloat(item.capital || 0);

                        const interes = parseFloat(item.interest || 0);

                        const mora = parseFloat(item.late_fee || 0);

                        const total =
                            parseFloat(item.total_amount || 0) +
                            parseFloat(item.late_fee || 0);

                        const saldo = parseFloat(item.remaining_balance || 0);

                        // =========================================
                        // PAGADO REAL
                        // =========================================

                        // =========================================
                        // BADGES
                        // =========================================

                        let badge = 'secondary';

                        if (item.status === 'pendiente') {
                            badge = 'warning';
                        }

                        if (item.status === 'pagado') {
                            badge = 'success';
                        }

                        if (item.status === 'vencido') {
                            badge = 'danger';
                        }

                        if (item.status === 'parcial') {
                            badge = 'info';
                        }

                        // =========================================
                        // FILA
                        // =========================================
                        const scheduleType = item.schedule_type || 'cuota';

                        let cuotaLabel = `Cuota ${item.installment_number}`;

                        // =====================================
                        // INICIAL
                        // =====================================

                        if (scheduleType === 'inicial') {

                            cuotaLabel = 'Cuota Inicial';

                        }

                        // =====================================
                        // CONTADO
                        // =====================================

                        if (scheduleType === 'contado') {

                            cuotaLabel = 'Pago Total';

                        }
                        html += `
                        <tr>

                            <td>
                                <strong>${index + 1}</strong>
                            </td>

                            <td>
                                <span class="badge badge-light px-3 py-2">
                                   ${cuotaLabel}
                                </span>
                            </td>

                            <td>
                                ${item.due_date}
                            </td>

                            <td class="text-primary font-weight-bold">
                                S/ ${capital.toFixed(2)}
                            </td>

                            <td>
                                S/ ${interes.toFixed(2)}
                            </td>

                            <td class="text-danger">
                                S/ ${mora.toFixed(2)}
                            </td>

                            <td class="font-weight-bold">
                                S/ ${total.toFixed(2)}
                            </td>

                            <td class="text-info font-weight-bold">
                                S/ ${saldo.toFixed(2)}
                            </td>

                            <td>
                                <span class="badge badge-${badge} px-3 py-2 rounded-pill">
                                    ${item.status.toUpperCase()}
                                </span>
                            </td>

                        </tr>
                    `;
                    });

                }

                // =================================================
                // INSERTAR TABLA
                // =================================================

                $('#paymentScheduleBody').html(html);

                // =================================================
                // CALCULAR DEUDA
                // =================================================


                // =================================================
                // PORCENTAJE
                // =================================================

                let porcentaje = 0;

                if (financiado > 0) {

                    porcentaje = (
                        (totalPagado / financiado) * 100
                    );

                }

                porcentaje = porcentaje.toFixed(1);

                // =================================================
                // CARDS
                // =================================================

                $('#ps_financed').text(
                    'S/ ' + financiado.toFixed(2)
                );

                $('#ps_paid').text(
                    'S/ ' + totalPagado.toFixed(2)
                );

                $('#ps_pending').text(
                    'S/ ' + deudaActual.toFixed(2)
                );

                // =================================================
                // PROGRESS BAR
                // =================================================

                $('#ps_progress_bar').css(
                    'width',
                    porcentaje + '%'
                );

                $('#ps_progress_text').text(
                    porcentaje + '% Pagado'
                );



            },

            error: function () {

                $('#paymentScheduleBody').html(`
                <tr>
                    <td colspan="9" class="text-center text-danger py-5">
                        Error al cargar cronograma.
                    </td>
                </tr>
            `);

            }

        });

    });
});

// =========================================================
// IMPRIMIR CRONOGRAMA
// =========================================================

$(document).on('click', '#btnPrintSchedule', function () {

    // =============================================
    // OBTENER CONTENIDO
    // =============================================

    const printContents = document.querySelector(
        '#paymentScheduleModal .modal-content'
    ).innerHTML;

    // =============================================
    // NUEVA VENTANA
    // =============================================

    const printWindow = window.open(
        '',
        '',
        'width=1200,height=900'
    );

    // =============================================
    // HTML
    // =============================================

    printWindow.document.write(`
        <html>

        <head>

            <title>
                Cronograma Financiero
            </title>

            <!-- BOOTSTRAP -->
            <link rel="stylesheet"
                href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

            <!-- FONT AWESOME -->
            <link rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

            <style>

                body {

                    background: white;
                    padding: 20px;
                    font-family: Arial, sans-serif;

                }

                .modal-footer,
                .close {

                    display: none !important;

                }

                .summary-card {

                    background: white;
                    border-radius: 18px;
                    padding: 18px;
                    border: 1px solid #edf2f7;
                    box-shadow: 0 2px 12px rgba(0,0,0,.04);
                    height: 100%;

                }

                .summary-card small {

                    display: block;
                    color: #94a3b8;
                    margin-bottom: 6px;
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;

                }

                .summary-info h4 {

                    color: #0284c7;

                }

                .summary-success h4 {

                    color: #16a34a;

                }

                .schedule-thead {

                    background: #f1f5f9;

                }

                .schedule-thead th {

                    border: none !important;
                    padding: 14px;
                    font-size: 12px;
                    font-weight: 700;
                    color: #334155;

                }

                table tbody td {

                    padding: 12px !important;
                    font-size: 12px;

                }

                .badge {

                    padding: 7px 12px;
                    border-radius: 50px;
                    font-size: 11px;
                    font-weight: 700;

                }

                @media print {

                    body {

                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;

                    }

                }

            </style>

        </head>

        <body>

            ${printContents}

        </body>

        </html>
    `);

    printWindow.document.close();

    // =============================================
    // ESPERAR Y IMPRIMIR
    // =============================================

    setTimeout(() => {

        printWindow.focus();

        printWindow.print();

    }, 800);

});

// =========================================================
// EXPORTAR PDF CRONOGRAMA
// =========================================================

// =========================================================
// EXPORTAR PDF CRONOGRAMA
// =========================================================

$(document).on('click', '#btnPdfSchedule', async function () {

    const btn = $(this);

    btn.prop('disabled', true);

    btn.html(`
        <span class="spinner-border spinner-border-sm mr-1"></span>
        Generando PDF...
    `);

    try {

        // =====================================================
        // CONTENEDOR REAL
        // =====================================================

        const element = document.querySelector(
            '#paymentScheduleModal .modal-content'
        );

        // =====================================================
        // QUITAR SCROLL Y EFECTOS
        // =====================================================

        $('#paymentScheduleModal')
            .removeClass('fade');

        $('#paymentScheduleModal .modal-dialog')
            .css({

                'transform': 'none',
                'max-width': '1400px',
                'width': '1400px'

            });

        $('#paymentScheduleModal .modal-content')
            .css({

                'border': 'none',
                'box-shadow': 'none',
                'background': '#ffffff'

            });

        $('#paymentScheduleModal .modal-body')
            .css({

                'overflow': 'visible',
                'max-height': 'none',
                'height': 'auto',
                'background': '#ffffff'

            });

        $('body').css({

            'overflow': 'visible',
            'background': '#ffffff'

        });

        // =====================================================
        // ESPERAR RENDER
        // =====================================================

        await new Promise(resolve =>
            setTimeout(resolve, 700)
        );

        // =====================================================
        // CAPTURA
        // =====================================================

        const canvas = await html2canvas(element, {

            scale: 2,

            useCORS: true,

            allowTaint: true,

            logging: false,

            backgroundColor: '#ffffff',

            removeContainer: true,

            foreignObjectRendering: false,

            scrollX: 0,
            scrollY: 0,

            windowWidth: element.scrollWidth,
            windowHeight: element.scrollHeight,

            onclone: function (clonedDoc) {

                // =====================================
                // MODAL CLONADO
                // =====================================

                const modal = clonedDoc.querySelector(
                    '#paymentScheduleModal'
                );

                if (modal) {

                    modal.style.opacity = '1';

                    modal.style.background = '#ffffff';

                    modal.style.filter = 'none';

                    modal.style.transform = 'none';

                }

                // =====================================
                // TODOS LOS ELEMENTOS
                // =====================================

                clonedDoc.querySelectorAll('*').forEach(el => {

                    el.style.opacity = '1';

                    el.style.filter = 'none';

                    el.style.backdropFilter = 'none';

                });

                // =====================================
                // BODY
                // =====================================

                clonedDoc.body.style.background =
                    '#ffffff';

            }

        });

        const imgData = canvas.toDataURL(
            'image/png',
            1.0
        );

        // =====================================================
        // PDF
        // =====================================================

        const { jsPDF } = window.jspdf;

        const pdf = new jsPDF(
            'p',
            'mm',
            'a4'
        );

        const pdfWidth =
            pdf.internal.pageSize.getWidth();

        const pdfHeight =
            pdf.internal.pageSize.getHeight();

        const imgWidth = pdfWidth;

        const imgHeight =
            (canvas.height * imgWidth) /
            canvas.width;

        let heightLeft = imgHeight;

        let position = 0;

        // =====================================================
        // PRIMERA PÁGINA
        // =====================================================

        pdf.addImage(

            imgData,

            'PNG',

            0,

            position,

            imgWidth,

            imgHeight

        );

        heightLeft -= pdfHeight;

        // =====================================================
        // MÁS PÁGINAS
        // =====================================================

        while (heightLeft > 0) {

            position = heightLeft - imgHeight;

            pdf.addPage();

            pdf.addImage(

                imgData,

                'PNG',

                0,

                position,

                imgWidth,

                imgHeight

            );

            heightLeft -= pdfHeight;

        }

        // =====================================================
        // EXPORTAR
        // =====================================================

        const saleCode =
            $('#ps_sale_code').text().trim();

        pdf.save(
            `Cronograma_${saleCode}.pdf`
        );

        // =====================================================
        // RESTAURAR
        // =====================================================

        $('#paymentScheduleModal')
            .addClass('fade');

        $('#paymentScheduleModal .modal-dialog')
            .attr('style', '');

        $('#paymentScheduleModal .modal-content')
            .attr('style', '');

        $('#paymentScheduleModal .modal-body')
            .attr('style', '');

        $('body').attr('style', '');

    } catch (error) {

        console.error(error);

        Swal.fire({

            icon: 'error',

            title: 'Error',

            text: 'No se pudo generar el PDF'

        });

    } finally {

        btn.prop('disabled', false);

        btn.html(`
            <i class="fas fa-file-pdf mr-1"></i>
            Exportar PDF
        `);

    }

});
