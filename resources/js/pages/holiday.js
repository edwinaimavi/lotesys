var divLoading = document.getElementById('divLoading');

let tableHoliday;

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

    tableHoliday = $('#tableHoliday').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.holidayList,

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
                data: 'date',
                name: 'date'
            },

            {
                data: 'description',
                name: 'description'
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

    $('#holidayForm').on('submit', function (e) {

        e.preventDefault();

        const btn = $('#btnSaveHoliday');

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
        // UPDATE / STORE
        // =========================================

        if (id) {

            url = "/admin/holidays/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeHoliday;

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
                    Guardar Feriado
                `);

                $('#holidayModal').modal('hide');

                tableHoliday.ajax.reload(null, false);

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
                    Guardar Feriado
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
        '.editHoliday',
        function () {

            const id = $(this).data('id');

            $('#holidayForm')
                .attr('data-id', id);

            $('#date')
                .val($(this).data('date'));

            $('#description')
                .val($(this).data('description'));

            $('#status')
                .val($(this).data('status'));

            $('.icon_modal').html(`
                <i class="far fa-edit text-primary"></i>
            `);

            $('#holidayModalLabel')
                .html('EDITAR FERIADO');

            $('#holidayModal')
                .modal('show');

        }
    );

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#holidayModal').on(
        'hidden.bs.modal',
        function () {

            const $form =
                $('#holidayForm');

            $form[0].reset();

            $form.removeAttr('data-id');

            $('#holidayModalLabel')
                .html('Nuevo Feriado');

            $form.find('.is-invalid')
                .removeClass('is-invalid');

            $('.invalid-feedback')
                .text('');

        }
    );

    // =========================================================
    // ELIMINAR
    // =========================================================

    $(document).on(
        'click',
        '.deleteHoliday',
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
                            `${window.routes.deleteHoliday}/${id}`,

                        type: 'DELETE',

                        success: function (response) {

                            tableHoliday
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
        '.viewHoliday',
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

            $('#vh_date').text(
                $(this).data('date') || '—'
            );

            $('#vh_description').text(
                $(this).data('description') || '—'
            );

            $('#vh_status')
                .removeClass(
                    'badge-success badge-danger badge-secondary'
                )
                .addClass(badgeClass)
                .text(
                    status
                        ? status.toUpperCase()
                        : '—'
                );

            $('#vh_status_text').text(
                status
                    ? status.toUpperCase()
                    : '—'
            );

            $('#vh_created_by').text(
                $(this).data('created_by') || '—'
            );

            $('#vh_updated_by').text(
                $(this).data('updated_by') || '—'
            );

            $('#vh_created_at').text(
                $(this).data('created_at') || '—'
            );

            $('#vh_updated_at').text(
                $(this).data('updated_at') || '—'
            );

            $('#vh_id').text(
                $(this).data('id') || '—'
            );

            $('#vh_created_by_user').text(
                $(this).data('created_by') || '—'
            );

            $('#vh_updated_by_user').text(
                $(this).data('updated_by') || '—'
            );

            $('#vh_updated_at_footer').text(
                $(this).data('updated_at') || '—'
            );

            $('#viewHolidayModal')
                .modal('show');

        }
    );

});