var divLoading = document.getElementById('divLoading');

let tableInvoice;
let apiAttempts = [];

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

document.addEventListener("DOMContentLoaded", function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    tableInvoice = $('#tableInvoice').DataTable({

        processing: true,

        serverSide: true,

        ajax: window.routes.invoiceList,

        order: [
            [1, 'desc']
        ],

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
                data: 'document_type',
                name: 'document_type'
            },

            {
                data: 'series',
                name: 'series'
            },

            {
                data: 'number',
                name: 'number'
            },

            {
                data: 'issue_date',
                name: 'issue_date'
            },

            {
                data: 'customer',
                name: 'customer_name'
            },

            {
                data: 'total_amount',
                name: 'total_amount'
            },

            {
                data: 'sunat_status',
                name: 'sunat_status'
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

            $('[data-toggle="tooltip"]').tooltip();

        }

    });

    $('#tableInvoice').on('click', '.btn-api-response', function () {
        loadApiLogs($(this).data('url'));
    });

    $('#apiAttemptSelect').on('change', function () {
        renderApiAttempt(apiAttempts[$(this).val()]);
    });

    $('#copyApiResponse').on('click', function () {
        const response = $('#apiResponseBody').text();
        if (!response) return;

        navigator.clipboard.writeText(response).then(() => {
            const button = $('#copyApiResponse');
            const original = button.html();
            button.html('<i class="fas fa-check mr-1"></i>Copiado');
            setTimeout(() => button.html(original), 1500);
        });
    });

});

function loadApiLogs(url) {
    apiAttempts = [];
    $('#apiResponseLoading').removeClass('d-none');
    $('#apiResponseError, #apiResponseContent').addClass('d-none');
    $('#apiResponseModal').modal('show');

    $.get(url)
        .done(function (response) {
            const invoice = response.invoice;
            const types = { invoice: 'Factura', receipt: 'Boleta', sale_note: 'Nota de venta', credit_note: 'Nota de Crédito', debit_note: 'Nota de Débito' };
            $('#apiInvoiceLabel').text(`${types[invoice.document_type] || invoice.document_type} ${invoice.series}-${invoice.number}`);
            apiAttempts = response.logs || [];

            if (!apiAttempts.length) {
                showApiError('No hay respuesta API registrada para este comprobante. Esta auditoría aplica para nuevas emisiones desde la actualización.');
                return;
            }

            const actionLabels = {
                send: 'Emisión',
                credit_note_send: 'Envío NC / SUNAT',
                credit_note_pdf: 'PDF NC',
                credit_note_xml: 'XML NC'
            };

            const options = apiAttempts.map((log, index) =>
                `<option value="${index}">#${log.id} · ${escapeHtml(actionLabels[log.action] || log.action || 'API')} · ${escapeHtml(log.created_at || 'Sin fecha')} · HTTP ${log.http_status ?? 'N/A'} · ${log.success ? 'Exitoso' : 'Fallido'}</option>`
            ).join('');

            $('#apiAttemptSelect').html(options);

            // Para notas de crédito, mostrar por defecto la respuesta que
            // confirma SUNAT (/note/send), no la descarga auxiliar PDF/XML.
            const preferredIndex = apiAttempts.findIndex(log =>
                log.action === 'credit_note_send' || log.action === 'send'
            );
            const initialIndex = preferredIndex >= 0 ? preferredIndex : 0;

            $('#apiAttemptSelect').val(String(initialIndex));
            renderApiAttempt(apiAttempts[initialIndex]);
            $('#apiResponseLoading').addClass('d-none');
            $('#apiResponseContent').removeClass('d-none');
        })
        .fail(function (xhr) {
            showApiError(xhr.responseJSON?.message || 'No se pudieron obtener los registros de la API.');
        });
}

function showApiError(message) {
    $('#apiResponseLoading, #apiResponseContent').addClass('d-none');
    $('#apiResponseError').text(message).removeClass('d-none');
}

function renderApiAttempt(log) {
    if (!log) return;

    const error = log.error_message || log.exception_message;
    const responseText = apiResponseText(log);
    const details = `
        <div class="row mb-3">
            <div class="col-md-3"><small class="text-muted d-block">Proveedor</small><strong>${escapeHtml(log.provider || 'APISPERU')}</strong></div>
            <div class="col-md-2"><small class="text-muted d-block">Acción</small><strong>${escapeHtml(log.action || '—')}</strong></div>
            <div class="col-md-2"><small class="text-muted d-block">HTTP</small><strong>${log.http_status ?? 'N/A'}</strong></div>
            <div class="col-md-2"><small class="text-muted d-block">Resultado</small><span class="badge ${log.success ? 'badge-success' : 'badge-danger'}">${log.success ? 'Exitoso' : 'Fallido'}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">Fecha</small><strong>${escapeHtml(log.created_at || '—')}</strong></div>
        </div>
        <div class="mb-3"><small class="text-muted d-block">Endpoint</small><code>${escapeHtml(log.endpoint || '—')}</code></div>
        ${error ? `<div class="alert alert-danger py-2"><strong>Error:</strong> ${escapeHtml(error)}</div>` : ''}
        <div class="d-flex justify-content-between align-items-center mb-1"><strong>Respuesta completa</strong></div>
        <pre id="apiResponseBody" class="api-code-block">${escapeHtml(responseText)}</pre>
        <details class="mt-3">
            <summary class="font-weight-bold text-info" style="cursor:pointer">Ver payload enviado</summary>
            <pre class="api-code-block mt-2">${escapeHtml(formatJson(log.request_payload) || 'Sin payload registrado.')}</pre>
        </details>`;

    $('#apiAttemptDetails').html(details);
}

function apiResponseText(log) {
    const body = formatJson(log.response_body);
    if (body) return body;

    const json = formatJson(log.response_json);
    if (json) return json;

    if (log.success && log.action === 'credit_note_xml') {
        return 'XML generado correctamente por APISPERU y almacenado en el comprobante. La aceptación SUNAT se consulta en la acción “Envío NC / SUNAT”.';
    }

    if (log.success && log.action === 'credit_note_pdf') {
        return 'PDF generado correctamente por APISPERU y almacenado en el comprobante. La respuesta binaria no se duplica en la auditoría.';
    }

    return 'Sin cuerpo de respuesta.';
}

function formatJson(value) {
    if (!value) return value;
    try { return JSON.stringify(JSON.parse(value), null, 2); } catch (_) { return value; }
}

function escapeHtml(value) {
    return $('<div>').text(String(value ?? '')).html();
}
