var divLoading = document.getElementById('divLoading');

let tableLot;

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
    // CARGAR MANZANAS SEGÚN PROYECTO
    // =========================================================

    $('#project_id').on('change', function () {

        const projectId = $(this).val();

        $('#block_id').html(`
            <option value="">
                Cargando...
            </option>
        `);

        if (!projectId) {

            $('#block_id').html(`
                <option value="">
                    Seleccione una manzana
                </option>
            `);

            return;
        }

        $.ajax({

            url: `/admin/projects/${projectId}/blocks`,

            type: 'GET',

            success: function (response) {

                let options = `
                    <option value="">
                        Seleccione una manzana
                    </option>
                `;

                response.forEach(block => {

                    options += `
                        <option value="${block.id}">
                            ${block.name}
                        </option>
                    `;
                });

                $('#block_id').html(options);

            },

            error: function () {

                $('#block_id').html(`
                    <option value="">
                        Error al cargar
                    </option>
                `);

            }

        });

    });



    // =========================================================
    // GENERAR CÓDIGO AUTOMÁTICO
    // =========================================================

    $('#block_id').on('change', function () {

        const projectId = $('#project_id').val();

        const blockId = $('#block_id').val();

        if (!projectId || !blockId) {

            $('#code').val('');

            return;
        }

        $.ajax({

            url: window.routes.generateLotCode,

            type: 'GET',

            data: {

                project_id: projectId,

                block_id: blockId

            },

            success: function (response) {

                $('#code').val(response.code);

            },

            error: function () {

                $('#code').val('');

            }

        });

    });

    // =========================================================
    // GUARDAR / ACTUALIZAR LOTE
    // =========================================================

    $('#lotForm').on('submit', function (e) {

        e.preventDefault();

        // =====================================================
        // EVITAR DOBLE ENVÍO
        // =====================================================

        const btn = $('#btnSaveLot');

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

            url = "/admin/lots/" + id;

            type = 'POST';

            formData.append('_method', 'PUT');

        } else {

            url = window.routes.storeLot;

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
    Guardar Lote
`);

                $('#lotModal').modal('hide');

                tableLot.ajax.reload(null, false);

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
    Guardar Lote
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
    // EDITAR LOTE
    // =========================================================

    $(document).on('click', '.editLot', function () {

        const id = $(this).data('id');

        const project_id = $(this).data('project_id');

        const block_id = $(this).data('block_id');

        const code = $(this).data('code');

        const number = $(this).data('number');

        const area = $(this).data('area');

        const unit_measure = $(this).data('unit_measure');

        const cash_price = $(this).data('cash_price');

        const financed_price = $(this).data('financed_price');

        const north_boundary = $(this).data('north_boundary');
        const south_boundary = $(this).data('south_boundary');
        const east_boundary = $(this).data('east_boundary');
        const west_boundary = $(this).data('west_boundary');

        const status = $(this).data('status');

        const observation = $(this).data('observation');

        $('#lotForm').attr('data-id', id);

        $('#project_id').val(project_id).trigger('change');

        setTimeout(() => {

            $('#block_id').val(block_id);

        }, 500);

        $('#code').val(code);

        $('#number').val(number);

        $('#area').val(area);

        $('#unit_measure').val(unit_measure);

        $('#cash_price').val(cash_price);

        $('#financed_price').val(financed_price);

        $('#north_boundary').val(north_boundary);
        $('#south_boundary').val(south_boundary);
        $('#east_boundary').val(east_boundary);
        $('#west_boundary').val(west_boundary);

        $('#status').val(status);

        $('#observation').val(observation);

        $('.icon_modal').html('<i class="far fa-edit text-primary"></i>');

        $('#lotModalLabel').html('EDITAR LOTE');

        $('#lotModal').modal('show');

    });

    // =========================================================
    // LIMPIAR MODAL
    // =========================================================

    $('#lotModal').on('hidden.bs.modal', function () {

        const $form = $('#lotForm');

        $form[0].reset();

        $form.removeAttr('data-id');

        $('#lotModalLabel').html('NUEVO LOTE');

        $('#block_id').html(`
            <option value="">
                Seleccione una manzana
            </option>
        `);

        $form.find('.is-invalid').removeClass('is-invalid');

        $('.invalid-feedback').text('');

    });

    // =========================================================
    // VER LOTE
    // =========================================================

    // =========================================================
    // VER LOTE
    // =========================================================

    $(document).on('click', '.viewLot', function () {

        const id = $(this).data('id');

        const project = $(this).data('project');

        const block = $(this).data('block');

        const code = $(this).data('code');

        const number = $(this).data('number');

        const area = $(this).data('area');

        const unit_measure = $(this).data('unit_measure');

        const cash_price = $(this).data('cash_price');

        const financed_price = $(this).data('financed_price');

        const north_boundary = $(this).data('north_boundary');

        const south_boundary = $(this).data('south_boundary');

        const east_boundary = $(this).data('east_boundary');

        const west_boundary = $(this).data('west_boundary');

        const status = $(this).data('status');

        const observation = $(this).data('observation');

        const created_at = $(this).data('created_at');

        const updated_at = $(this).data('updated_at');

        const created_by = $(this).data('created_by');

        const updated_by = $(this).data('updated_by');

        // =====================================================
        // DATOS GENERALES
        // =====================================================

        $('#vl_id').text(id || '—');

        $('#vl_project').text(project || '—');

        $('#vl_project_name').text(project || '—');

        $('#vl_block').text(block || '—');

        $('#vl_code').text(code || '—');

        $('#vl_number').text(number || '—');

        $('#vl_area').text(area || '—');

        $('#vl_unit_measure').text(unit_measure || '—');

        // =====================================================
        // PRECIOS
        // =====================================================

        $('#vl_cash_price').text(
            cash_price
                ? 'S/ ' + parseFloat(cash_price).toFixed(2)
                : 'S/ 0.00'
        );

        $('#vl_financed_price').text(
            financed_price
                ? 'S/ ' + parseFloat(financed_price).toFixed(2)
                : 'S/ 0.00'
        );

        // =====================================================
        // COLINDANTES
        // =====================================================

        $('#vl_north_boundary').text(north_boundary || '—');

        $('#vl_south_boundary').text(south_boundary || '—');

        $('#vl_east_boundary').text(east_boundary || '—');

        $('#vl_west_boundary').text(west_boundary || '—');

        // =====================================================
        // OBSERVACIÓN
        // =====================================================

        $('#vl_observation').text(observation || '—');

        // =====================================================
        // INFORMACIÓN SISTEMA
        // =====================================================

        $('#vl_created_at').text(created_at || '—');

        $('#vl_updated_at').text(updated_at || '—');

        $('#vl_created_by_user').text(created_by || '—');

        $('#vl_updated_by_user').text(updated_by || '—');

        // =====================================================
        // STATUS
        // =====================================================

        let badgeClass = 'badge badge-secondary';

        switch (status) {

            case 'disponible':
                badgeClass = 'badge badge-success';
                break;

            case 'separado':
                badgeClass = 'badge badge-warning';
                break;

            case 'vendido':
                badgeClass = 'badge badge-primary';
                break;

            case 'rescindido':
                badgeClass = 'badge badge-danger';
                break;

            case 'bloqueado':
                badgeClass = 'badge badge-dark';
                break;
        }

        $('#vl_status')
            .removeClass()
            .addClass(`${badgeClass} py-2 px-3`)
            .text(status);

        $('#viewLotModal').modal('show');

    });
    // =========================================================
    // FILTRO EMPRESA
    // =========================================================

    $('#filter_company').on('change', function () {

        const companyId = $(this).val();

        $('#filter_project').html(`
        <option value="">
            Cargando...
        </option>
    `);

        if (!companyId) {

            $('#filter_project').html(`
            <option value="">
                Todos
            </option>
        `);

            tableLot.ajax.reload();

            return;
        }

        $.ajax({

            url: `${window.routes.getProjects}/${companyId}/projects`,

            type: 'GET',

            success: function (response) {

                let options = `
                <option value="">
                    Todos
                </option>
            `;

                response.forEach(project => {

                    options += `
                    <option value="${project.id}">
                        ${project.name}
                    </option>
                `;
                });

                $('#filter_project').html(options);

            }

        });

        tableLot.ajax.reload();

    });

    // =========================================================
    // FILTRO PROYECTO
    // =========================================================

    $('#filter_project').on('change', function () {

        const projectId = $(this).val();

        $('#filter_block').html(`
        <option value="">
            Cargando...
        </option>
    `);

        if (!projectId) {

            $('#filter_block').html(`
            <option value="">
                Todas
            </option>
        `);

            tableLot.ajax.reload();

            return;
        }

        $.ajax({

            url: `${window.routes.getBlocks}/${projectId}/blocks`,

            type: 'GET',

            success: function (response) {

                let options = `
                <option value="">
                    Todas
                </option>
            `;

                response.forEach(block => {

                    options += `
                    <option value="${block.id}">
                        ${block.name}
                    </option>
                `;
                });

                $('#filter_block').html(options);

            }

        });

        tableLot.ajax.reload();

    });

    // =========================================================
    // FILTROS
    // =========================================================

    $('#filter_block').on('change', function () {

        tableLot.ajax.reload();

    });

    $('#filter_lot').on('keyup', function () {

        tableLot.ajax.reload();

    });

    // =========================================================
    // DATATABLE
    // =========================================================

    tableLot = $('#tableLot').DataTable({

        processing: true,

        serverSide: true,

        ajax: {

            url: window.routes.lotList,

            data: function (d) {

                d.company_id = $('#filter_company').val();

                d.project_id = $('#filter_project').val();

                d.block_id = $('#filter_block').val();

                d.lot_number = $('#filter_lot').val();

            }

        },

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
                data: 'block',
                name: 'block'
            },

            {
                data: 'code',
                name: 'code'
            },

            {
                data: 'number',
                name: 'number'
            },

            {
                data: 'area',
                name: 'area'
            },

            {
                data: 'cash_price',
                name: 'cash_price'
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
    // ELIMINAR LOTE
    // =========================================================

    $(document).on('click', '.deleteLot', function () {

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

                    url: `${window.routes.deleteLot}/${id}`,

                    type: 'DELETE',

                    success: function (response) {

                        tableLot.ajax.reload(null, false);

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