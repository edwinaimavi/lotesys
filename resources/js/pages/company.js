var divLoading = document.getElementById('divLoading');

let tableCompany;

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
    // GUARDAR / ACTUALIZAR COMPANY
    // =========================================================

    $('#companyForm').on('submit', function (e) {

        e.preventDefault();

        divLoading.style.display = "flex";

        const $form = $(this);

        const id = $form.attr('data-id');

        let url = '';
        let type = '';

        const formData = new FormData(this);

        if (id) {

            url = "/admin/companies/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeCompany;

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

                $('#companyModal').modal('hide');

                tableCompany.ajax.reload(null, false);

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
    // EDITAR COMPANY
    // =========================================================

    $(document).on('click', '.editCompany', function () {

        const id = $(this).data('id');

        const business_name = $(this).data('business_name');

        const trade_name = $(this).data('trade_name');

        const ruc = $(this).data('ruc');

        const address = $(this).data('address');

        const phone = $(this).data('phone');

        const email = $(this).data('email');

        const status = $(this).data('status');

        $('#companyForm').attr('data-id', id);

        $('#business_name').val(business_name);

        $('#trade_name').val(trade_name);

        $('#ruc').val(ruc);

        $('#address').val(address);

        $('#phone').val(phone);

        $('#email').val(email);

        $('#status').val(status);

        $('.icon_modal').html('<i class="far fa-edit text-primary"></i>');

        $('#companyModalLabel').html('EDITAR EMPRESA');

        $('#companyModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#companyModal').on('hidden.bs.modal', function () {

        const $form = $('#companyForm');

        $form[0].reset();

        $form.removeAttr('data-id');

        $('#companyModalLabel').html('NUEVA EMPRESA');

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });

    // =========================================================
    // VER COMPANY
    // =========================================================

    $(document).on('click', '.viewCompany', function () {

        const business_name = $(this).data('business_name');

        const trade_name = $(this).data('trade_name');

        const ruc = $(this).data('ruc');

        const address = $(this).data('address');

        const phone = $(this).data('phone');

        const email = $(this).data('email');

        const status = $(this).data('status');

        const id = $(this).data('id');

        const created_at = $(this).data('created_at');

        const updated_at = $(this).data('updated_at');

        const created_by = $(this).data('created_by');

        const updated_by = $(this).data('updated_by');

        // =========================================
        // DATOS PRINCIPALES
        // =========================================

        $('#vc_business_name').text(business_name || '—');

        $('#vc_trade_name').text(trade_name || '—');

        $('#vc_ruc').text(ruc || '—');

        $('#vc_address').text(address || '—');

        $('#vc_phone').text(phone || '—');

        $('#vc_email').text(email || '—');

        $('#vc_id').text(id || '—');

        $('#vc_created_at').text(created_at || '—');

        $('#vc_updated_at').text(updated_at || '—');

        $('#vc_created_by').text(created_by || '—');

        $('#vc_creator_user').text(created_by || '—');

        $('#vc_updater_user').text(updated_by || '—');

        // =========================================
        // STATUS
        // =========================================

        if (status == 1) {

            $('#vc_status')
                .removeClass()
                .addClass('badge badge-success py-2 px-3')
                .text('Activo');

            $('#vc_status_text').text('Activo');

            $('#vc_current_status').html(
                '<span class="badge badge-success px-3 py-2">Activo</span>'
            );

        } else {

            $('#vc_status')
                .removeClass()
                .addClass('badge badge-danger py-2 px-3')
                .text('Inactivo');

            $('#vc_status_text').text('Inactivo');

            $('#vc_current_status').html(
                '<span class="badge badge-danger px-3 py-2">Inactivo</span>'
            );
        }

        $('#viewCompanyModal').modal('show');

    });

    // =========================================================
    // DATATABLE
    // =========================================================

    tableCompany = $('#tableCompany').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.companyList,

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
                data: 'business_name',
                name: 'business_name'
            },

            {
                data: 'trade_name',
                name: 'trade_name'
            },

            {
                data: 'ruc',
                name: 'ruc'
            },

            {
                data: 'phone',
                name: 'phone'
            },

            {
                data: 'email',
                name: 'email'
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

        language: {
            url: "/vendor/datatables/js/i18n/es-ES.json"
        },

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
    // ELIMINAR COMPANY
    // =========================================================

    $(document).on('click', '.deleteCompany', function () {

        const id = $(this).data('id');

        Swal.fire({

            title: 'Are you sure?',

            text: 'This action cannot be undone.',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonText: 'Yes, delete',

            cancelButtonText: 'Cancel'

        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    url: `${window.routes.deleteCompany}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableCompany.ajax.reload(null, false);

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
                            text: 'An error occurred while deleting.'
                        });

                    }

                });

            }

        });

    });

});