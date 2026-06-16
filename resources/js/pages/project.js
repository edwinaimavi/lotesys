var divLoading = document.getElementById('divLoading');

let tableProject;

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
    // GENERAR CÓDIGO AUTOMÁTICO
    // =========================================================

    function generateProjectCode() {

        $.ajax({

            url: window.routes.generateProjectCode,

            type: 'GET',

            success: function (response) {

                $('#code').val(response.code);

            },

            error: function () {

                console.error('Error generating project code');

            }

        });

    }

    // =========================================================
    // GUARDAR / ACTUALIZAR PROJECT
    // =========================================================

    $('#projectForm').on('submit', function (e) {

        e.preventDefault();

        // =====================================================
        // EVITAR DOBLE ENVÍO
        // =====================================================

        const btn = $('#btnSaveProject');

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

            url = "/admin/projects/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeProject;

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
    Guardar Proyecto
`);

                $('#projectModal').modal('hide');

                tableProject.ajax.reload(null, false);

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
    Guardar Proyecto
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
    // EDITAR PROJECT
    // =========================================================

    $(document).on('click', '.editProject', function () {

        const id = $(this).data('id');

        const company_id = $(this).data('company_id');

        const name = $(this).data('name');

        const code = $(this).data('code');

        const description = $(this).data('description');

        const address = $(this).data('address');

        const district = $(this).data('district');

        const province = $(this).data('province');

        const department = $(this).data('department');

        const total_area = $(this).data('total_area');

        const registry_number = $(this).data('registry_number');

        const start_date = $(this).data('start_date');

        const status = $(this).data('status');

        $('#projectForm').attr('data-id', id);

        $('#company_id').val(company_id);

        $('#name').val(name);

        $('#code').val(code);

        $('#description').val(description);

        $('#address').val(address);

        $('#district').val(district);

        $('#province').val(province);

        $('#department').val(department);

        $('#total_area').val(total_area);

        $('#registry_number').val(registry_number);

        $('#start_date').val(start_date);

        $('#status').val(status);

        $('.icon_modal').html('<i class="far fa-edit text-primary"></i>');

        $('#projectModalLabel').html('EDITAR PROYECTO');

        $('#projectModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#projectModal').on('hidden.bs.modal', function () {

        const $form = $('#projectForm');

        $form[0].reset();

        $form.removeAttr('data-id');

        $('#projectModalLabel').html('NUEVO PROYECTO');

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });


    // =========================================================
    // ABRIR MODAL NUEVO PROJECT
    // =========================================================

    $('#projectModal').on('show.bs.modal', function () {

        const id = $('#projectForm').attr('data-id');

        // SOLO PARA NUEVO
        if (!id) {

            generateProjectCode();
        }

    });

    // =========================================================
    // VER PROJECT
    // =========================================================

    $(document).on('click', '.viewProject', function () {

        const id = $(this).data('id');

        const company = $(this).data('company');

        const name = $(this).data('name');

        const code = $(this).data('code');

        const description = $(this).data('description');

        const address = $(this).data('address');

        const district = $(this).data('district');

        const province = $(this).data('province');

        const department = $(this).data('department');

        const total_area = $(this).data('total_area');
        const registry_number = $(this).data('registry_number');

        const start_date = $(this).data('start_date');

        const status = $(this).data('status');

        const created_at = $(this).data('created_at');

        const updated_at = $(this).data('updated_at');

        const created_by = $(this).data('created_by');

        const updated_by = $(this).data('updated_by');

        const blocks_count = $(this).data('blocks_count');

        const lots_count = $(this).data('lots_count');

        const blocks = $(this).data('blocks');

        // =========================================================
        // DATOS
        // =========================================================

        $('#vp_id').text(id || '—');

        $('#vp_company').text(company || '—');

        $('#vp_name').text(name || '—');

        $('#vp_code').text(code || '—');

        $('#vp_description').text(description || '—');

        $('#vp_address').text(address || '—');

        $('#vp_district').text(district || '—');

        $('#vp_province').text(province || '—');

        $('#vp_department').text(department || '—');

        $('#vp_total_area').text(total_area || '—');
        $('#vp_registry_number').text(registry_number || '—');

        $('#vp_start_date').text(start_date || '—');

        $('#vp_created_at').text(created_at || '—');

        $('#vp_updated_at').text(updated_at || '—');

        $('#vp_created_by_user').text(created_by || '—');

        $('#vp_updated_by_user').text(updated_by || '—');

        $('#vp_blocks_count').text(blocks_count || 0);

        $('#vp_lots_count').text(lots_count || 0);

        $('#vp_blocks').text(blocks || 'Sin manzanas registradas');

        // =========================================================
        // STATUS
        // =========================================================

        if (status == 1) {

            $('#vp_status')
                .removeClass()
                .addClass('badge badge-success py-2 px-3')
                .text('Activo');

        } else {

            $('#vp_status')
                .removeClass()
                .addClass('badge badge-danger py-2 px-3')
                .text('Inactivo');
        }

        $('#viewProjectModal').modal('show');

    });

    // =========================================================
    // DATATABLE
    // =========================================================

    tableProject = $('#tableProject').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.projectList,

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
                data: 'company',
                name: 'company'
            },

            {
                data: 'name',
                name: 'name'
            },

            {
                data: 'code',
                name: 'code'
            },

            {
                data: 'district',
                name: 'district'
            },

            {
                data: 'province',
                name: 'province'
            },

            {
                data: 'department',
                name: 'department'
            },

            {
                data: 'start_date',
                name: 'start_date'
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
    // ELIMINAR PROJECT
    // =========================================================

    $(document).on('click', '.deleteProject', function () {

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

                    url: `${window.routes.deleteProject}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableProject.ajax.reload(null, false);

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