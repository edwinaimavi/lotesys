$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | DATATABLE REPORTE DE VENTAS
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | CARGAR EMPRESAS
    |--------------------------------------------------------------------------
    */

    $.get(window.routes.reportCompanies, function (companies) {

        companies.forEach(function (company) {

            $("#company_id").append(
                `<option value="${company.id}">
                ${company.trade_name}
            </option>`
            );

        });

    });

    /*
    |--------------------------------------------------------------------------
    | EMPRESA -> PROYECTOS
    |--------------------------------------------------------------------------
    */

    $("#company_id").on("change", function () {

        let companyId = $(this).val();

        // Reiniciar selects hijos
        $("#project_id")
            .empty()
            .append('<option value="">-- Todos --</option>')
            .val("");

        $("#block_id")
            .empty()
            .append('<option value="">-- Todas --</option>')
            .val("");

        if (!companyId) {
            return;
        }

        let url = window.routes.reportProjects.replace(
            ':companyId',
            companyId
        );

        $.get(url, function (projects) {

            projects.forEach(function (project) {

                $("#project_id").append(
                    `<option value="${project.id}">
                    ${project.name}
                </option>`
                );

            });

        });

    });

    /*
    |--------------------------------------------------------------------------
    | PROYECTO -> MANZANAS
    |--------------------------------------------------------------------------
    */

    $("#project_id").on("change", function () {

        let projectId = $(this).val();

        $("#block_id")
            .empty()
            .append('<option value="">-- Todas --</option>')
            .val("");

        if (!projectId) {
            return;
        }

        let url = window.routes.reportBlocks.replace(
            ':projectId',
            projectId
        );

        $.get(url, function (blocks) {

            blocks.forEach(function (block) {

                $("#block_id").append(
                    `<option value="${block.id}">
                    ${block.name}
                </option>`
                );

            });

        });

    });

    if ($("#tableSalesReport").length) {

        let table = $("#tableSalesReport").DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: window.routes.salesReportList,
                data: function (d) {
                    d.company_id = $("#company_id").val();
                    d.project_id = $("#project_id").val();
                    d.block_id = $("#block_id").val();
                    d.sale_type = $("#sale_type").val();
                    d.date_from = $("#date_from").val();
                    d.date_to = $("#date_to").val();
                    console.log(d);
                }
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
                    data: "sale_date",
                    name: "sale_date"
                },
                {
                    data: "customer_name",
                    name: "customer.full_name",
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
                    data: "sale_type",
                    name: "sale_type"
                },
                {
                    data: "total_price",
                    name: "total_price"
                },
                {
                    data: "status",
                    name: "status"
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
        | BOTÓN BUSCAR
        |--------------------------------------------------------------------------
        */

        $("#btnSearch").on("click", function () {

            // Si no hay empresa, limpiar proyecto y manzana
            if ($("#company_id").val() == "") {
                $("#project_id").val("");
                $("#block_id").val("");
            }

            // Si no hay proyecto, limpiar manzana
            if ($("#project_id").val() == "") {
                $("#block_id").val("");
            }

            table.ajax.reload(null, true);
        });
    }

});

/*
|--------------------------------------------------------------------------
| EXPORTAR EXCEL
|--------------------------------------------------------------------------
*/

$("#btnExportExcel").on("click", function () {

    let params = $.param({
        company_id: $("#company_id").val(),
        project_id: $("#project_id").val(),
        block_id: $("#block_id").val(),
        sale_type: $("#sale_type").val(),
        date_from: $("#date_from").val(),
        date_to: $("#date_to").val(),
    });

    window.open(
        window.routes.salesReportExcel + "?" + params,
        "_blank"
    );

});

/*
|--------------------------------------------------------------------------
| EXPORTAR PDF
|--------------------------------------------------------------------------
*/

$("#btnExportPdf").on("click", function () {

    let params = $.param({
        company_id: $("#company_id").val(),
        project_id: $("#project_id").val(),
        block_id: $("#block_id").val(),
        sale_type: $("#sale_type").val(),
        date_from: $("#date_from").val(),
        date_to: $("#date_to").val(),
    });

    window.open(
        window.routes.salesReportPdf + "?" + params,
        "_blank"
    );

});