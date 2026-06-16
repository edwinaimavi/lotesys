var divLoading = document.getElementById('divLoading');

let tableBank;

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

    tableBank = $('#tableBank').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.bankList,

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
                data: 'bank_name',
                name: 'bank_name'
            },

            {
                data: 'currency',
                name: 'currency'
            },

            {
                data: 'account_number',
                name: 'account_number'
            },

            {
                data: 'cci',
                name: 'cci'
            },

            {
                data: 'account_holder',
                name: 'account_holder'
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

    $('#bankForm').on('submit', function (e) {

        e.preventDefault();

        const btn = $('#btnSaveBank');

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

            url = "/admin/banks/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeBank;

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
                    Guardar Banco
                `);

                $('#bankModal').modal('hide');

                tableBank.ajax.reload(null, false);

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
                    Guardar Banco
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

    $(document).on('click', '.editBank', function () {

        const id = $(this).data('id');

        $('#bankForm').attr('data-id', id);

        $('#bank_name').val($(this).data('bank_name'));

        $('#currency').val($(this).data('currency'));

        $('#account_number').val($(this).data('account_number'));

        $('#cci').val($(this).data('cci'));

        $('#account_holder').val($(this).data('account_holder'));

        $('#description').val($(this).data('description'));

        $('#status').val($(this).data('status'));

        $('.icon_modal').html(`
            <i class="far fa-edit text-primary"></i>
        `);

        $('#bankModalLabel').html('EDITAR BANCO');

        $('#bankModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#bankModal').on('hidden.bs.modal', function () {

        const $form = $('#bankForm');

        $form[0].reset();

        $form.removeAttr('data-id');

        $('#bankModalLabel').html('NUEVO BANCO');

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });

    // =========================================================
    // ELIMINAR
    // =========================================================

    $(document).on('click', '.deleteBank', function () {

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

                    url: `${window.routes.deleteBank}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableBank.ajax.reload(null, false);

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
    // VER DETALLE
    // =========================================================

    $(document).on('click', '.viewBank', function () {

        const status = $(this).data('status');

        let badgeClass = 'badge-secondary';

        if (status === 'activo') {
            badgeClass = 'badge-success';
        }

        if (status === 'inactivo') {
            badgeClass = 'badge-danger';
        }

        $('#vb_bank_name').text(
            $(this).data('bank_name') || '—'
        );

        $('#vb_currency').text(
            $(this).data('currency') || '—'
        );

        $('#vb_account_number').text(
            $(this).data('account_number') || '—'
        );

        $('#vb_cci').text(
            $(this).data('cci') || '—'
        );

        $('#vb_account_holder').text(
            $(this).data('account_holder') || '—'
        );


        // =========================================================
        // PANEL DERECHO
        // =========================================================

        $('#vb_bank_name_text').text(
            $(this).data('bank_name') || '—'
        );

        $('#vb_currency_text').text(
            $(this).data('currency') || '—'
        );

        $('#vb_account_number_detail').text(
            $(this).data('account_number') || '—'
        );

        $('#vb_cci_detail').text(
            $(this).data('cci') || '—'
        );

        $('#vb_account_holder_detail').text(
            $(this).data('account_holder') || '—'
        );

        $('#vb_bank_name_detail').text(
            $(this).data('bank_name') || '—'
        );

        $('#vb_currency_detail').text(
            $(this).data('currency') || '—'
        );

        $('#vb_status_text').text(
            status ? status.toUpperCase() : '—'
        );

        $('#vb_description').text(
            $(this).data('description') || '—'
        );

        $('#vb_status')
            .removeClass('badge-success badge-danger badge-secondary')
            .addClass(badgeClass)
            .text(status ? status.toUpperCase() : '—');

        $('#vb_created_by').text(
            $(this).data('created_by') || '—'
        );

        $('#vb_updated_by').text(
            $(this).data('updated_by') || '—'
        );

        $('#vb_created_at').text(
            $(this).data('created_at') || '—'
        );

        $('#vb_updated_at').text(
            $(this).data('updated_at') || '—'
        );

        $('#viewBankModal').modal('show');

        $('#vb_id').text(
            $(this).data('id') || '—'
        );

        $('#vb_created_by_user').text(
            $(this).data('created_by') || '—'
        );

        $('#vb_updated_by_user').text(
            $(this).data('updated_by') || '—'
        );

    });

});