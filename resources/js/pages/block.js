var divLoading = document.getElementById('divLoading');

let tableBlock;

$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
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
    // GUARDAR / ACTUALIZAR MANZANA
    // =========================================================

    $('#blockForm').on('submit', function (e) {

        // =====================================================
        // EVITAR DOBLE ENVÍO
        // =====================================================

        const btn = $('#btnSaveBlock');

        if (btn.prop('disabled')) {
            return;
        }

        btn.prop('disabled', true);

        btn.html(`
    <span class="spinner-border spinner-border-sm mr-1"></span>
    Guardando...
`);

        e.preventDefault();

        divLoading.style.display = "flex";

        const $form = $(this);

        const id = $form.attr('data-id');

        let url = '';
        let type = '';

        const formData = new FormData(this);

        if (id) {

            url = "/admin/blocks/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeBlock;

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

                $('#blockModal').modal('hide');

                tableBlock.ajax.reload(null, false);


                btn.prop('disabled', false);

                btn.html(`
    <i class="fas fa-save mr-1"></i>
    Guardar Manzana
`);

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
    Guardar Manzana
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
    // EDITAR MANZANA
    // =========================================================

    $(document).on('click', '.editBlock', function () {

        const id = $(this).data('id');

        const project_id = $(this).data('project_id');

        const name = $(this).data('name');

        const description = $(this).data('description');

        const status = $(this).data('status');

        $('#blockForm').attr('data-id', id);

        $('#project_id').val(project_id);

        $('#name').val(name);

        $('#description').val(description);

        $('#status').val(status);

        $('.icon_modal').html('<i class="far fa-edit text-primary"></i>');

        $('#blockModalLabel').html('EDITAR MANZANA');

        $('#blockModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#blockModal').on('hidden.bs.modal', function () {

        const $form = $('#blockForm');

        $form[0].reset();

        $form.removeAttr('data-id');

        $('#blockModalLabel').html('NUEVA MANZANA');

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });

    // =========================================================
    // VER MANZANA
    // =========================================================

    $(document).on('click', '.viewBlock', function () {

        const id = $(this).data('id');

        const project = $(this).data('project');

        const name = $(this).data('name');

        const description = $(this).data('description');

        const status = $(this).data('status');

        const created_at = $(this).data('created_at');

        const updated_at = $(this).data('updated_at');

        const created_by = $(this).data('created_by');

        const updated_by = $(this).data('updated_by');

        const lots_count = $(this).data('lots_count');

        // =========================================================
        // DATOS
        // =========================================================

        $('#vb_id').text(id || '—');

        $('#vb_project').text(project || '—');

        $('#vb_project_name').text(project || '—');

        $('#vb_status_text').text(status == 1 ? 'Activo' : 'Inactivo');

        $('#vb_name').text(name || '—');

        $('#vb_description').text(description || '—');

        $('#vb_created_at').text(created_at || '—');

        $('#vb_updated_at').text(updated_at || '—');

        $('#vb_created_by_user').text(created_by || '—');

        $('#vb_updated_by_user').text(updated_by || '—');

        $('#vb_lots_count').text(lots_count || 0);

        // =========================================================
        // STATUS
        // =========================================================

        if (status == 1) {

            $('#vb_status')
                .removeClass()
                .addClass('badge badge-success py-2 px-3')
                .text('Activo');

        } else {

            $('#vb_status')
                .removeClass()
                .addClass('badge badge-danger py-2 px-3')
                .text('Inactivo');
        }

        $('#viewBlockModal').modal('show');

    });

    // =========================================================
    // ABRIR MODAL GENERAR LOTES
    // =========================================================

    $(document).on('click', '.generateLots', function () {

        const projectId = $(this).data('project_id');

        const project = $(this).data('project');

        const blockId = $(this).data('block_id');

        const block = $(this).data('block');

        // =====================================================
        // SET DATA
        // =====================================================

        $('#gl_project_id').val(projectId);

        $('#gl_block_id').val(blockId);

        $('#gl_project_name').text(project || '—');

        $('#gl_block_name').text(block || '—');

        // =====================================================
        // RESET FORM
        // =====================================================

        $('#generateLotsForm')[0].reset();

        $('#gl_project_id').val(projectId);

        $('#gl_block_id').val(blockId);

        $('#generateLotsModal').modal('show');

    });


    // =========================================================
    // GENERAR LOTES MASIVOS
    // =========================================================

    $('#generateLotsForm').on('submit', function (e) {

        e.preventDefault();

        divLoading.style.display = "flex";

        const formData = new FormData(this);

        $.ajax({

            url: window.routes.generateLots,

            type: 'POST',

            data: formData,

            processData: false,

            contentType: false,

            success: function (response) {

                divLoading.style.display = "none";

                $('#generateLotsModal').modal('hide');

                tableBlock.ajax.reload(null, false);

                Swal.fire({

                    icon: 'success',

                    title: 'Proceso completado',

                    html: `
                    <div class="text-left">
                        <b>Lotes creados:</b> ${response.created}<br>
                        <b>Lotes omitidos:</b> ${response.skipped}
                    </div>
                `,

                    confirmButtonText: 'Entendido'

                });

            },

            error: function (xhr) {

                divLoading.style.display = "none";

                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text: xhr.responseJSON?.message || 'Ocurrió un error.'

                });

            }

        });

    });


    // =========================================================
    // LIMPIAR MODAL GENERAR LOTES
    // =========================================================

    $('#generateLotsModal').on('hidden.bs.modal', function () {

        $('#generateLotsForm')[0].reset();

    });

    // =========================================================
    // DATATABLE
    // =========================================================

    tableBlock = $('#tableBlock').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.blockList,

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
                data: 'project',
                name: 'project'
            },

            {
                data: 'name',
                name: 'name'
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
    // ELIMINAR MANZANA
    // =========================================================

    $(document).on('click', '.deleteBlock', function () {

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

                    url: `${window.routes.deleteBlock}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableBlock.ajax.reload(null, false);

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

});