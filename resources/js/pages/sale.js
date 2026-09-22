var divLoading = document.getElementById('divLoading');

let tableSale;

function formatSaleDateForDisplay(dateValue) {
    const dateParts = String(dateValue || '').split('T')[0].split('-');

    if (dateParts.length !== 3) {
        return dateValue || 'â€”';
    }

    return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
}

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
    // CARGAR LOTES SEGUN EL MODO DEL FORMULARIO
    // =========================================================

    function loadAvailableLots(selectedLotId = null) {

        const $lotSelect = $('#lot_id');

        const data = {};

        if (selectedLotId) {
            data.selected_lot_id = selectedLotId;
        }

        $lotSelect
            .prop('disabled', true)
            .empty()
            .append(new Option('Seleccione un lote', ''));

        return $.ajax({

            url: window.routes.availableLots,

            type: 'GET',

            data: data

        }).done(function (lots) {

            lots.forEach(function (lot) {

                const option = new Option(
                    lot.text,
                    lot.id,
                    false,
                    false
                );

                $(option)
                    .attr('data-company', lot.company || '')
                    .attr('data-project', lot.project || '')
                    .attr('data-block', lot.block || '')
                    .attr('data-lot_number', lot.lot_number || '')
                    .attr('data-lot_code', lot.lot_code || '')
                    .attr('data-cash_price', lot.cash_price)
                    .attr('data-financed_price', lot.financed_price);

                $lotSelect.append(option);

            });

            $lotSelect.val(
                selectedLotId ? String(selectedLotId) : ''
            ).trigger('change');

        }).fail(function (xhr) {

            console.error('Error loading available lots', xhr);

        }).always(function () {

            $lotSelect.prop('disabled', false);

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

            loadAvailableLots();

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

        const $button = $(this);

        const id = $button.data('id');

        const lotId = $button.data('lot_id');

        $('#saleForm').attr('data-id', id);

        $('#sale_code').val($(this).data('sale_code'));

        $('#customer_id').val($(this).data('customer_id'));

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

        loadAvailableLots(lotId).done(function () {

            // El cambio del lote actualiza Select2 y sus precios asociados.
            // Restauramos los importes guardados para no alterar la venta.
            $('#lot_price').val($button.data('lot_price'));

            $('#initial_payment').val($button.data('initial_payment'));

            $('#balance_finance').val($button.data('balance_finance'));

            $('#installments_count').val($button.data('installments_count'));

            $('#monthly_payment').val($button.data('monthly_payment'));

            $('#interest_rate').val($button.data('interest_rate'));

            $('#first_payment_date').val($button.data('first_payment_date'));

            $('#payment_day').val($button.data('payment_day'));

            $('#saleModal').modal('show');

        });

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
                data: 'company',
                name: 'company'
            },

            {
                data: 'project',
                name: 'project'
            },

            {
                data: 'lot_location',
                name: 'lot_location'
            },

            {
                data: 'lot_code',
                name: 'lot_code'
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

        $('#vs_empresa').text(
            $(this).data('company') || '—'
        );

        $('#vs_empresa_ruc').text(
            $(this).data('company_ruc') || '—'
        );

        $('#vs_proyecto').text(
            $(this).data('project') || '—'
        );

        $('#vs_manzana').text(
            $(this).data('block') || '—'
        );

        $('#vs_lote_numero').text(
            $(this).data('lot_number') || '—'
        );

        $('#vs_lote_codigo').text(
            $(this).data('lot_code') || $(this).data('lot') || '—'
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

        $('#ps_company').text(
            $(this).data('company') || '—'
        );

        $('#ps_project').text(
            $(this).data('project') || '—'
        );

        const scheduleBlock = $(this).data('block') || '—';
        const scheduleLotNumber = $(this).data('lot_number') || '—';
        const scheduleLotCode = $(this).data('lot_code') || '—';

        $('#ps_lot').text(
            `${scheduleBlock} · Lote ${scheduleLotNumber} · ${scheduleLotCode}`
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

                        const installmentNumber = parseInt(
                            item.installment_number,
                            10
                        );
                        const visualNumber = installmentNumber + 1;
                        let cuotaLabel = `Cuota ${visualNumber}`;

                        // =====================================
                        // INICIAL
                        // =====================================

                        if (scheduleType === 'inicial') {

                            cuotaLabel = `Cuota ${visualNumber} - Inicial`;

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
                                ${formatSaleDateForDisplay(item.due_date)}
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
// UTILIDADES DE IMPRESIÓN / PDF DEL CRONOGRAMA
// =========================================================

function buildScheduleExportClone() {

    const source = document.querySelector(
        '#paymentScheduleModal .schedule-modal-content'
    );

    if (!source) {
        return null;
    }

    const host = document.createElement('div');

    host.className = 'schedule-export-host schedule-scope';

    const clone = source.cloneNode(true);

    const footer = clone.querySelector('.schedule-footer');

    if (footer) {
        footer.remove();
    }

    const closeButton = clone.querySelector('.close');

    if (closeButton) {
        closeButton.remove();
    }

    host.appendChild(clone);

    document.body.appendChild(host);

    return {
        host,
        element: clone
    };

}

function getScheduleStyles() {

    const modal = document.querySelector('#paymentScheduleModal');

    const style = modal
        ? modal.nextElementSibling
        : null;

    if (
        style &&
        style.tagName === 'STYLE'
    ) {
        return style.innerHTML;
    }

    return '';

}

// =========================================================
// IMPRIMIR CRONOGRAMA
// =========================================================

$(document).on('click', '#btnPrintSchedule', function () {

    const exportClone = buildScheduleExportClone();

    if (!exportClone) {
        return;
    }

    const printableHtml = exportClone.element.outerHTML;

    exportClone.host.remove();

    const printWindow = window.open(
        '',
        '',
        'width=1400,height=900'
    );

    if (!printWindow) {

        Swal.fire({
            icon: 'warning',
            title: 'Ventana bloqueada',
            text: 'Permite ventanas emergentes para imprimir el cronograma.'
        });

        return;

    }

    const scheduleStyles = getScheduleStyles();

    printWindow.document.open();

    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Cronograma Financiero</title>

            <link rel="stylesheet"
                href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

            <link rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

            <style>
                ${scheduleStyles}

                body {
                    margin: 0;
                    padding: 12px;
                    background: #ffffff;
                    font-family: Arial, sans-serif;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                .schedule-scope .schedule-modal-content {
                    width: 100% !important;
                    max-height: none !important;
                    height: auto !important;
                    overflow: visible !important;
                    box-shadow: none !important;
                    border: 0 !important;
                }

                .schedule-scope .schedule-body,
                .schedule-scope .schedule-table-wrap,
                .schedule-scope .table-responsive {
                    max-height: none !important;
                    height: auto !important;
                    overflow: visible !important;
                }

                .schedule-scope .schedule-footer,
                .schedule-scope .close {
                    display: none !important;
                }

                @page {
                    size: A4 landscape;
                    margin: 8mm;
                }
            </style>
        </head>

        <body>
            <div class="schedule-scope">
                ${printableHtml}
            </div>
        </body>
        </html>
    `);

    printWindow.document.close();

    printWindow.onload = function () {

        setTimeout(() => {

            printWindow.focus();

            printWindow.print();

        }, 250);

    };

});

// =========================================================
// EXPORTAR PDF CRONOGRAMA
// =========================================================

$(document).on('click', '#btnPdfSchedule', async function () {

    const btn = $(this);

    const originalHtml = btn.html();

    btn.prop('disabled', true);

    btn.html(`
        <span class="spinner-border spinner-border-sm mr-1"></span>
        Generando PDF...
    `);

    let exportClone = null;

    try {

        exportClone = buildScheduleExportClone();

        if (!exportClone) {
            throw new Error('No se encontró el cronograma para exportar.');
        }

        await new Promise(resolve => setTimeout(resolve, 180));

        const element = exportClone.element;

        const canvas = await html2canvas(element, {

            scale: 1.6,
            useCORS: true,
            allowTaint: true,
            logging: false,
            backgroundColor: '#ffffff',
            scrollX: 0,
            scrollY: 0,
            windowWidth: 1280,
            windowHeight: Math.max(
                element.scrollHeight,
                900
            )

        });

        const { jsPDF } = window.jspdf;

        const pdf = new jsPDF(
            'l',
            'mm',
            'a4'
        );

        const margin = 6;

        const pdfWidth =
            pdf.internal.pageSize.getWidth();

        const pdfHeight =
            pdf.internal.pageSize.getHeight();

        const usableWidth =
            pdfWidth - (margin * 2);

        const usableHeight =
            pdfHeight - (margin * 2) - 4;

        const pixelsPerMm =
            canvas.width / usableWidth;

        const pageHeightPx =
            Math.floor(
                usableHeight * pixelsPerMm
            );

        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    canvas.height / pageHeightPx
                )
            );

        let sourceY = 0;
        let pageNumber = 1;

        while (sourceY < canvas.height) {

            const sliceHeight = Math.min(
                pageHeightPx,
                canvas.height - sourceY
            );

            const pageCanvas =
                document.createElement('canvas');

            pageCanvas.width =
                canvas.width;

            pageCanvas.height =
                sliceHeight;

            const context =
                pageCanvas.getContext('2d');

            context.fillStyle = '#ffffff';

            context.fillRect(
                0,
                0,
                pageCanvas.width,
                pageCanvas.height
            );

            context.drawImage(
                canvas,
                0,
                sourceY,
                canvas.width,
                sliceHeight,
                0,
                0,
                canvas.width,
                sliceHeight
            );

            if (pageNumber > 1) {
                pdf.addPage();
            }

            const sliceHeightMm =
                sliceHeight / pixelsPerMm;

            const imageData =
                pageCanvas.toDataURL(
                    'image/jpeg',
                    0.95
                );

            pdf.addImage(
                imageData,
                'JPEG',
                margin,
                margin,
                usableWidth,
                sliceHeightMm,
                undefined,
                'FAST'
            );

            pdf.setFontSize(7);

            pdf.setTextColor(
                110,
                120,
                130
            );

            pdf.text(
                `Página ${pageNumber} de ${totalPages}`,
                pdfWidth - margin,
                pdfHeight - 3,
                {
                    align: 'right'
                }
            );

            sourceY += sliceHeight;

            pageNumber++;

        }

        const saleCode =
            $('#ps_sale_code')
                .text()
                .trim()
                .replace(
                    /[^A-Za-z0-9_-]+/g,
                    '_'
                );

        pdf.save(
            `Cronograma_${saleCode || 'Venta'}.pdf`
        );

    } catch (error) {

        console.error(
            'Error al generar PDF de cronograma:',
            error
        );

        Swal.fire({

            icon: 'error',
            title: 'No se pudo generar el PDF',
            text: 'El cronograma no pudo exportarse completo. Intenta nuevamente.'

        });

    } finally {

        if (
            exportClone &&
            exportClone.host
        ) {
            exportClone.host.remove();
        }

        btn.prop('disabled', false);

        btn.html(originalHtml);

    }

});

// =============================================================
// VENTA MÚLTIPLE · FLUJO ADITIVO
// Mantiene intacto el formulario de venta simple existente.
// =============================================================
$(function () {

    const $multiModal = $('#multiSaleModal');
    const $multiForm = $('#multiSaleForm');
    const $multiLots = $('#multi_lot_ids');

    if (!$multiModal.length || !$multiForm.length || !$multiLots.length) {
        return;
    }

    let multiLotsCatalog = new Map();
    let multiLotsLoading = false;

    function money(value) {
        return 'S/ ' + (parseFloat(value || 0) || 0).toFixed(2);
    }

    function initMultiSelect2() {
        if (typeof $.fn.select2 === 'undefined') {
            return;
        }

        const selects = [
            {
                selector: '#multi_customer_id',
                placeholder: 'Seleccione un cliente'
            },
            {
                selector: '#multi_lot_ids',
                placeholder: 'Seleccione 2 o más lotes'
            }
        ];

        selects.forEach(function (item) {
            const $select = $(item.selector);

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                dropdownParent: $multiModal,
                placeholder: item.placeholder,
                closeOnSelect: item.selector !== '#multi_lot_ids'
            });
        });
    }

    function generateMultiSaleCode() {
        return $.ajax({
            url: window.routes.generateSaleCode,
            type: 'GET'
        }).done(function (response) {
            $('#multi_sale_code').val(response.code || '');
        });
    }

    function loadMultiAvailableLots() {
        if (multiLotsLoading) {
            return $.Deferred().reject().promise();
        }

        multiLotsLoading = true;
        multiLotsCatalog = new Map();

        $multiLots
            .prop('disabled', true)
            .empty()
            .trigger('change');

        return $.ajax({
            url: window.routes.availableLots,
            type: 'GET'
        }).done(function (lots) {
            lots.forEach(function (lot) {
                const normalized = {
                    id: String(lot.id),
                    company_id: String(lot.company_id || ''),
                    company: lot.company || '—',
                    project_id: String(lot.project_id || ''),
                    project: lot.project || '—',
                    block_id: String(lot.block_id || ''),
                    block: lot.block || '—',
                    lot_number: lot.lot_number || '—',
                    lot_code: lot.lot_code || '—',
                    area: lot.area,
                    unit_measure: lot.unit_measure || 'm²',
                    cash_price: parseFloat(lot.cash_price || 0) || 0,
                    financed_price: parseFloat(lot.financed_price || 0) || 0,
                    text: lot.text || `Lote ${lot.lot_number || lot.id}`
                };

                multiLotsCatalog.set(normalized.id, normalized);

                const option = new Option(
                    normalized.text,
                    normalized.id,
                    false,
                    false
                );

                $multiLots.append(option);
            });
        }).fail(function (xhr) {
            console.error('Error loading multi-sale lots', xhr);

            Swal.fire({
                icon: 'error',
                title: 'No se pudieron cargar los lotes',
                text: 'Actualiza la página e intenta nuevamente.'
            });
        }).always(function () {
            multiLotsLoading = false;
            $multiLots.prop('disabled', false);
        });
    }

    function selectedMultiLots() {
        return ($multiLots.val() || [])
            .map(function (id) {
                return multiLotsCatalog.get(String(id));
            })
            .filter(Boolean);
    }

    function multiPriceForLot(lot) {
        return $('#multi_sale_type').val() === 'contado'
            ? lot.cash_price
            : lot.financed_price;
    }

    function renderMultiLots() {
        const lots = selectedMultiLots();
        const $container = $('#multi_selected_lots');

        $('#multi_lot_counter').text(`${lots.length} seleccionados`);
        $('#multi_summary_count').text(`${lots.length} ${lots.length === 1 ? 'lote' : 'lotes'}`);

        if (!lots.length) {
            $container.html(`
                <div class="multi-empty-lots">
                    <i class="fas fa-map mr-2"></i>
                    Seleccione los lotes que formarán parte de la venta.
                </div>
            `);
            return;
        }

        let html = '';

        lots.forEach(function (lot) {
            const area = lot.area
                ? `${lot.area} ${lot.unit_measure || 'm²'}`
                : 'Área no registrada';

            html += `
                <div class="multi-lot-row">
                    <div class="min-w-0">
                        <div class="multi-lot-name">
                            ${escapeMultiSaleHtml(lot.block)} · Lote ${escapeMultiSaleHtml(lot.lot_number)}
                            <span class="text-muted">· ${escapeMultiSaleHtml(lot.lot_code)}</span>
                        </div>
                        <div class="multi-lot-meta">
                            ${escapeMultiSaleHtml(lot.company)} · ${escapeMultiSaleHtml(lot.project)} · ${escapeMultiSaleHtml(area)}
                        </div>
                    </div>
                    <div class="multi-lot-price">${money(multiPriceForLot(lot))}</div>
                </div>
            `;
        });

        $container.html(html);
    }

    function escapeMultiSaleHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function calculateMultiFinance() {
        const lots = selectedMultiLots();
        const total = lots.reduce(function (sum, lot) {
            return sum + multiPriceForLot(lot);
        }, 0);

        const saleType = $('#multi_sale_type').val();
        let initial = parseFloat($('#multi_initial_payment').val() || 0) || 0;
        let installments = parseInt($('#multi_installments_count').val() || 0, 10) || 0;
        const paymentMode = $('#multi_payment_mode').val();

        if (saleType === 'contado') {
            initial = 0;
            installments = 1;
            $('#multi_initial_payment').val('0.00').prop('readonly', true);
            $('#multi_installments_count').val(1).prop('readonly', true);
            $('#multi_payment_mode').val('automatico');
            $('#multi_custom_payment_container').addClass('d-none');
            $('#multi_interest_rate').val('0.00').prop('readonly', true);

            const saleDate = $('#multi_sale_date').val();
            $('#multi_first_payment_date').val(saleDate).prop('readonly', true);

            if (saleDate) {
                $('#multi_payment_day').val(parseInt(saleDate.split('-')[2], 10)).prop('readonly', true);
            }
        } else {
            $('#multi_initial_payment').prop('readonly', false);
            $('#multi_installments_count').prop('readonly', false);
            $('#multi_interest_rate').prop('readonly', false);
            $('#multi_first_payment_date').prop('readonly', false);
            $('#multi_payment_day').prop('readonly', false);
        }

        let balance = Math.max(total - initial, 0);
        let monthly = 0;

        if (saleType === 'contado') {
            balance = total;
            monthly = total;
        } else if ($('#multi_payment_mode').val() === 'personalizado') {
            monthly = parseFloat($('#multi_custom_payment').val() || 0) || 0;
        } else if (installments > 0) {
            monthly = balance / installments;
        }

        $('#multi_lot_price').val(total.toFixed(2));
        $('#multi_balance_finance').val(balance.toFixed(2));
        $('#multi_monthly_payment').val(monthly.toFixed(2));

        $('#multi_total_price_label').text(money(total));
        $('#multi_balance_label').text(money(balance));
        $('#multi_monthly_label').text(money(monthly));

        renderMultiLots();
    }

    function ensureSameMultiProject() {
        const ids = $multiLots.val() || [];

        if (ids.length <= 1) {
            return true;
        }

        const lots = ids
            .map(id => multiLotsCatalog.get(String(id)))
            .filter(Boolean);

        const firstProject = lots[0]?.project_id;
        const invalid = lots.some(lot => lot.project_id !== firstProject);

        if (!invalid) {
            return true;
        }

        const correctedIds = ids.slice(0, -1);

        $multiLots
            .val(correctedIds)
            .trigger('change.select2');

        Swal.fire({
            icon: 'warning',
            title: 'Proyecto diferente',
            text: 'En esta primera versión todos los lotes de la venta deben pertenecer al mismo proyecto.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500
        });

        return false;
    }

    function toggleMultiCustomPayment() {
        const personalized = $('#multi_payment_mode').val() === 'personalizado'
            && $('#multi_sale_type').val() !== 'contado';

        $('#multi_custom_payment_container')
            .toggleClass('d-none', !personalized);

        if (!personalized) {
            $('#multi_custom_payment').val('');
        }

        calculateMultiFinance();
    }

    function toggleMultiLegacyFields() {
        const active = $('#multi_is_legacy_sale').is(':checked');

        $('#multi_legacy_fields').toggleClass('d-none', !active);
        $('#multi_collection_rules_start_date, #multi_legacy_observation')
            .prop('disabled', !active);

        if (!active) {
            $('#multi_collection_rules_start_date, #multi_legacy_observation').val('');
        }
    }

    function resetMultiSaleForm() {
        $multiForm[0].reset();

        $('#multi_status').val('activo');
        $('#multi_sale_type').val('financiado');
        $('#multi_payment_mode').val('automatico');
        $('#multi_initial_payment').val('0.00').prop('readonly', false);
        $('#multi_installments_count').val(1).prop('readonly', false);
        $('#multi_interest_rate').val('0.00').prop('readonly', false);
        $('#multi_first_payment_date').prop('readonly', false);
        $('#multi_payment_day').prop('readonly', false);
        $('#multi_custom_payment_container').addClass('d-none');
        $('#multi_is_legacy_sale').prop('checked', false);
        toggleMultiLegacyFields();

        $multiLots.val(null).trigger('change');
        $('#multi_customer_id').val(null).trigger('change');

        $multiForm.find('.is-invalid').removeClass('is-invalid');
        $multiForm.find('.invalid-feedback').text('');

        calculateMultiFinance();
    }

    $multiModal.on('show.bs.modal', function () {
        resetMultiSaleForm();
        initMultiSelect2();
        generateMultiSaleCode();
        loadMultiAvailableLots();

        const saleDate = $('#multi_sale_date').val();
        $('#multi_first_payment_date').val(saleDate);

        if (saleDate) {
            $('#multi_payment_day').val(parseInt(saleDate.split('-')[2], 10));
        }
    });

    $multiModal.on('shown.bs.modal', function () {
        initMultiSelect2();
    });

    $multiLots.on('change', function () {
        if (!ensureSameMultiProject()) {
            calculateMultiFinance();
            return;
        }

        $('#multi_lot_ids-error').text('');
        calculateMultiFinance();
    });

    $('#multi_sale_type').on('change', function () {
        toggleMultiCustomPayment();
        calculateMultiFinance();
    });

    $('#multi_initial_payment, #multi_installments_count, #multi_custom_payment')
        .on('keyup change', calculateMultiFinance);

    $('#multi_payment_mode').on('change', toggleMultiCustomPayment);

    $('#multi_sale_date').on('change', function () {
        const value = $(this).val();

        if ($('#multi_sale_type').val() === 'financiado' && value) {
            $('#multi_first_payment_date').val(value);
            $('#multi_payment_day').val(parseInt(value.split('-')[2], 10));
        }

        calculateMultiFinance();
    });

    $('#multi_is_legacy_sale').on('change', toggleMultiLegacyFields);

    $multiForm.on('submit', function (e) {
        e.preventDefault();

        const ids = $multiLots.val() || [];

        if (ids.length < 2) {
            $('#multi_lot_ids-error').text('Seleccione al menos 2 lotes.');
            $multiLots.addClass('is-invalid');
            return;
        }

        if (!ensureSameMultiProject()) {
            return;
        }

        calculateMultiFinance();

        const btn = $('#btnSaveMultiSale');

        if (btn.prop('disabled')) {
            return;
        }

        btn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Guardando...
        `);

        if (divLoading) {
            divLoading.style.display = 'flex';
        }

        const formData = new FormData(this);

        $.ajax({
            url: window.routes.storeMultipleSale,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (divLoading) {
                    divLoading.style.display = 'none';
                }

                btn.prop('disabled', false).html(`
                    <i class="fas fa-layer-group mr-1"></i>
                    Guardar venta múltiple
                `);

                $multiModal.modal('hide');
                tableSale.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: response.message || 'Venta múltiple registrada correctamente.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3200
                });
            },
            error: function (xhr) {
                if (divLoading) {
                    divLoading.style.display = 'none';
                }

                btn.prop('disabled', false).html(`
                    <i class="fas fa-layer-group mr-1"></i>
                    Guardar venta múltiple
                `);

                $multiForm.find('.is-invalid').removeClass('is-invalid');
                $multiForm.find('.invalid-feedback').text('');

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};

                    $.each(errors, function (key, messages) {
                        const message = Array.isArray(messages)
                            ? messages[0]
                            : messages;

                        const normalizedKey = key.startsWith('lot_ids')
                            ? 'lot_ids'
                            : key;

                        $(`#multi_${normalizedKey}`).addClass('is-invalid');
                        $(`#multi_${normalizedKey}-error`).text(message || 'Dato inválido.');
                    });

                    Swal.fire({
                        icon: 'warning',
                        title: 'Revisa la información',
                        text: 'Hay datos de la venta múltiple que necesitan corrección.'
                    });

                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo registrar',
                    text: xhr.responseJSON?.message || 'Ocurrió un error al registrar la venta múltiple.'
                });
            }
        });
    });

});

// =============================================================
// PRESENTACIÓN DE LOTES ASOCIADOS EN VENTA MÚLTIPLE
// =============================================================
$(document).on('click', '.viewSale', function () {
    const $button = $(this);

    setTimeout(function () {
        const isMultiple = parseInt($button.data('is_multiple') || 0, 10) === 1;
        const encodedLots = $button.attr('data-sale_lots') || '';

        $('#vs_lots_multiple_wrapper').addClass('d-none');
        $('#vs_lots_multiple').empty();
        $('#vs_location_title').text('Ubicación e identificación del lote');
        $('#vs_precio_label').text('Precio del lote');

        if (!isMultiple || !encodedLots) {
            return;
        }

        let lots = [];

        try {
            lots = JSON.parse(decodeURIComponent(encodedLots));
        } catch (error) {
            console.error('No se pudo interpretar los lotes de la venta.', error);
            return;
        }

        if (!Array.isArray(lots) || lots.length === 0) {
            return;
        }

        const first = lots[0];

        $('#vs_location_title').text('Ubicación e identificación de los lotes');
        $('#vs_precio_label').text('Precio total de los lotes');
        $('#vs_empresa').text(first.company || '—');
        $('#vs_empresa_ruc').text(first.company_ruc || '—');
        $('#vs_proyecto').text(first.project || '—');
        $('#vs_manzana').text('Varios');
        $('#vs_lote_numero').text(`${lots.length} lotes`);
        $('#vs_lote_codigo').text('Venta múltiple');

        const html = lots.map(function (lot) {
            const area = lot.area
                ? `${lot.area} ${lot.unit_measure || 'm²'}`
                : 'Área no registrada';

            return `
                <div class="sale-multi-lot-chip">
                    <strong>${$('<div>').text(`${lot.block || '—'} · Lote ${lot.lot_number || '—'}`).html()}</strong>
                    <span>${$('<div>').text(`${lot.lot_code || '—'} · ${area}`).html()}</span>
                </div>
            `;
        }).join('');

        $('#vs_lots_multiple').html(html);
        $('#vs_lots_multiple_wrapper').removeClass('d-none');
    }, 0);
});

$(document).on('click', '.viewSchedule', function () {
    const $button = $(this);

    setTimeout(function () {
        const lotsSummary = $button.data('lots_summary');

        if (lotsSummary) {
            $('#ps_lot').text(lotsSummary);
        }
    }, 0);
});
