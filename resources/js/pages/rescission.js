$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    /*
    |--------------------------------------------------------------------------
    | DATATABLE
    |--------------------------------------------------------------------------
    */

    let table = $("#tableRescissions").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: window.routes.rescissionList,
        },
        columns: [
            {
                data: "id",
                name: "id"
            },
            {
                data: "sale_code",
                name: "sale_code"
            },
            {
                data: "customer_name",
                orderable: false,
                searchable: false
            },
            {
                data: "company_name",
                orderable: false,
                searchable: false
            },
            {
                data: "project_name",
                orderable: false,
                searchable: false
            },
            {
                data: "lot_name",
                orderable: false,
                searchable: false
            },
            {
                data: "sale_date",
                name: "sale_date"
            },
            {
                data: "overdue_installments",
                searchable: false
            },
            {
                data: "amount_paid",
                searchable: false
            },
            {
                data: "status",
                searchable: false
            },
            {
                data: "actions",
                orderable: false,
                searchable: false
            }
        ],
        language: {
            url: "/vendor/datatables/js/i18n/es-ES.json"
        },
        order: [
            [0, "desc"]
        ]
    });

    /*
    |--------------------------------------------------------------------------
    | LIMPIAR MODAL
    |--------------------------------------------------------------------------
    */

    function resetForm() {

        $("#formRescission")[0].reset();

        $(".error-text").html("");

        $("#sale_id").val("");

        $("#txt_sale_code").html("-");
        $("#txt_customer_name").html("-");
        $("#txt_company_name").html("-");
        $("#txt_project_name").html("-");
        $("#txt_lot_name").html("-");
        $("#txt_sale_date").html("-");
        $("#txt_overdue_installments").html("-");
        $("#txt_amount_paid").html("S/ 0.00");
        $("#txt_balance_finance").html("S/ 0.00");

        $("#amount_paid").val("0.00");
        let today = new Date().toISOString().split('T')[0];
        $("#rescission_date").val(today);

        $("#overdue_installments").val(0);
        $("#penalty_amount").val("0.00");
    }

    $('#rescissionModal').on('hidden.bs.modal', function () {
        resetForm();
    });

    /*
    |--------------------------------------------------------------------------
    | ABRIR MODAL
    |--------------------------------------------------------------------------
    */

    $(document).on("click", ".btnRescind", function () {

        resetForm();

        let saleId = $(this).data("id");
        console.log("Click botón rescindir");
        console.log("Sale ID:", saleId);
        console.log(window.routes.showRescission + "/" + saleId + "/show");

        $.ajax({
            url: window.routes.showRescission + "/" + saleId + "/show",
            type: "GET",
            beforeSend: function () {
                Swal.fire({
                    title: "Cargando...",
                    text: "Obteniendo información del contrato.",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function (response) {

                Swal.close();

                $("#sale_id").val(response.id);

                $("#txt_sale_code").html(response.sale_code);
                $("#txt_customer_name").html(response.customer_name);
                $("#txt_company_name").html(response.company_name);
                $("#txt_project_name").html(response.project_name);
                $("#txt_lot_name").html(response.lot_name);
                $("#txt_sale_date").html(response.sale_date);

                $("#txt_overdue_installments").html(
                    response.overdue_installments
                );

                $("#txt_amount_paid").html(
                    "S/ " + parseFloat(response.amount_paid).toFixed(2)
                );

                $("#txt_balance_finance").html(
                    "S/ " + parseFloat(response.balance_finance).toFixed(2)
                );

                $("#amount_paid").val(
                    parseFloat(response.amount_paid).toFixed(2)
                );

                $("#overdue_installments").val(
                    response.overdue_installments
                );

                $("#rescissionModal").modal("show");
            },
            error: function () {
                Swal.fire(
                    "Error",
                    "No se pudo cargar la información de la venta.",
                    "error"
                );
            }
        });

    });

    /*
    |--------------------------------------------------------------------------
    | GUARDAR RESCISIÓN
    |--------------------------------------------------------------------------
    */

    $("#formRescission").submit(function (e) {

        e.preventDefault();

        $(".error-text").html("");

        Swal.fire({
            title: "¿Confirmar rescisión?",
            text: "La venta pasará a estado RESCINDIDO y el lote volverá a estar DISPONIBLE.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Sí, confirmar",
            cancelButtonText: "Cancelar"
        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }

            $.ajax({

                url: window.routes.storeRescission,
                type: "POST",
                data: $(this).serialize(),

                beforeSend: function () {

                    $("#btnSaveRescission")
                        .prop("disabled", true)
                        .html(
                            '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...'
                        );
                },

                success: function (response) {

                    $("#btnSaveRescission")
                        .prop("disabled", false)
                        .html(
                            '<i class="fas fa-file-signature mr-1"></i> Confirmar Rescisión'
                        );

                    $("#rescissionModal").modal("hide");

                    Swal.fire({
                        icon: "success",
                        title: "Correcto",
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    table.ajax.reload(null, false);

                },

                error: function (xhr) {

                    $("#btnSaveRescission")
                        .prop("disabled", false)
                        .html(
                            '<i class="fas fa-file-signature mr-1"></i> Confirmar Rescisión'
                        );

                    if (xhr.status === 422) {

                        $.each(xhr.responseJSON.errors, function (key, value) {
                            $("#error_" + key).html(value[0]);
                        });

                    } else {

                        Swal.fire(
                            "Error",
                            xhr.responseJSON?.message || "Ocurrió un error inesperado.",
                            "error"
                        );
                    }

                }

            });

        });

    });

});