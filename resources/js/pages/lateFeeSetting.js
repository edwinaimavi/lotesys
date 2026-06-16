var divLoading = document.getElementById('divLoading');

let tableLateFeeSetting;

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
    // DATATABLE
    // =========================================================

    tableLateFeeSetting = $('#tableLateFeeSetting').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.lateFeeSettingList,

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
                data: 'grace_days',
                name: 'grace_days'
            },

            {
                data: 'daily_late_fee',
                name: 'daily_late_fee'
            },

            {
                data: 'max_late_fee',
                name: 'max_late_fee'
            },

            {
                data: 'apply_sundays',
                name: 'apply_sundays'
            },

            {
                data: 'apply_holidays',
                name: 'apply_holidays'
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

            divLoading &&
                divLoading.classList.remove('d-none');

        },

        drawCallback: function () {

            divLoading &&
                divLoading.classList.add('d-none');

        }

    });

    // =========================================================
    // GUARDAR / ACTUALIZAR
    // =========================================================

    $('#lateFeeSettingForm').on('submit', function (e) {

        e.preventDefault();

        const btn = $('#btnSaveLateFeeSetting');

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

        // =========================================
        // SWITCHES
        // =========================================

        formData.set(
            'apply_sundays',
            $('#apply_sundays').is(':checked') ? 1 : 0
        );

        formData.set(
            'apply_holidays',
            $('#apply_holidays').is(':checked') ? 1 : 0
        );

        // =========================================
        // UPDATE / STORE
        // =========================================

        if (id) {

            url = "/admin/lateFeeSettings/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeLateFeeSetting;

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
                    Guardar Configuración
                `);

                $('#lateFeeSettingModal').modal('hide');

                tableLateFeeSetting.ajax.reload(null, false);

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
                    Guardar Configuración
                `);

                if (xhr.status === 422) {

                    const errors =
                        xhr.responseJSON.errors || {};

                    $('.is-invalid')
                        .removeClass('is-invalid');

                    $('.invalid-feedback')
                        .text('');

                    $.each(errors, function (key, messages) {

                        const input = $(`#${key}`);

                        input.addClass('is-invalid');

                        $(`#${key}-error`)
                            .text(messages[0]);

                    });

                } else {

                    Swal.fire({

                        icon: 'error',

                        title: 'Error',

                        text:
                            xhr.responseJSON?.message ||
                            'Unexpected error',

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

    $(document).on(
        'click',
        '.editLateFeeSetting',
        function () {

            const id = $(this).data('id');

            $('#lateFeeSettingForm')
                .attr('data-id', id);

            $('#grace_days')
                .val($(this).data('grace_days'));

            $('#daily_late_fee')
                .val($(this).data('daily_late_fee'));

            $('#max_late_fee')
                .val($(this).data('max_late_fee'));

            $('#status')
                .val($(this).data('status'));

            $('#apply_sundays')
                .prop(
                    'checked',
                    $(this).data('apply_sundays') == 1
                );

            $('#apply_holidays')
                .prop(
                    'checked',
                    $(this).data('apply_holidays') == 1
                );

            $('.icon_modal').html(`
                <i class="far fa-edit text-primary"></i>
            `);

            $('#lateFeeSettingModalLabel')
                .html('EDITAR CONFIGURACIÓN');

            $('#lateFeeSettingModal')
                .modal('show');

        }
    );

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#lateFeeSettingModal').on(
        'hidden.bs.modal',
        function () {

            const $form =
                $('#lateFeeSettingForm');

            $form[0].reset();

            $form.removeAttr('data-id');

            $('#lateFeeSettingModalLabel')
                .html('Nueva Configuración de Mora');

            $form.find('.is-invalid')
                .removeClass('is-invalid');

            $('.invalid-feedback')
                .text('');

            $('#apply_sundays')
                .prop('checked', false);

            $('#apply_holidays')
                .prop('checked', false);

        }
    );

    // =========================================================
    // ELIMINAR
    // =========================================================

    $(document).on(
        'click',
        '.deleteLateFeeSetting',
        function () {

            const id = $(this).data('id');

            Swal.fire({

                title: '¿Está seguro?',

                text:
                    'Esta acción no podrá revertirse.',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Sí, eliminar',

                cancelButtonText: 'Cancelar'

            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url:
                            `${window.routes.deleteLateFeeSetting}/${id}`,

                        type: 'DELETE',

                        success: function (response) {

                            tableLateFeeSetting
                                .ajax
                                .reload(null, false);

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

                                text:
                                    'Ocurrió un error al eliminar.'

                            });

                        }

                    });

                }

            });

        }
    );

    // =========================================================
    // VER DETALLE
    // =========================================================

    $(document).on(
        'click',
        '.viewLateFeeSetting',
        function () {

            const status =
                $(this).data('status');

            let badgeClass = 'badge-secondary';

            if (status === 'activo') {

                badgeClass = 'badge-success';

            }

            if (status === 'inactivo') {

                badgeClass = 'badge-danger';

            }

            $('#vls_grace_days').text(
                $(this).data('grace_days') || '0'
            );

            $('#vls_daily_late_fee').text(
                'S/ ' +
                parseFloat(
                    $(this).data('daily_late_fee') || 0
                ).toFixed(2)
            );

            $('#vls_max_late_fee').text(
                'S/ ' +
                parseFloat(
                    $(this).data('max_late_fee') || 0
                ).toFixed(2)
            );

            $('#vls_apply_sundays').html(

                $(this).data('apply_sundays') == 1

                    ? '<span class="badge badge-success">SI</span>'

                    : '<span class="badge badge-danger">NO</span>'

            );

            $('#vls_apply_holidays').html(

                $(this).data('apply_holidays') == 1

                    ? '<span class="badge badge-success">SI</span>'

                    : '<span class="badge badge-danger">NO</span>'

            );

            $('#vls_status')
                .removeClass(
                    'badge-success badge-danger badge-secondary'
                )
                .addClass(badgeClass)
                .text(
                    status
                        ? status.toUpperCase()
                        : '—'
                );

            $('#vls_created_by').text(
                $(this).data('created_by') || '—'
            );

            $('#vls_updated_by').text(
                $(this).data('updated_by') || '—'
            );

            $('#vls_created_at').text(
                $(this).data('created_at') || '—'
            );

            $('#vls_updated_at').text(
                $(this).data('updated_at') || '—'
            );

            $('#viewLateFeeSettingModal')
                .modal('show');

        }
    );

});