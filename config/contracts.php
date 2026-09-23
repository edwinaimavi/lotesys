<?php

// The sole catalog of contract fields, their sources and presentation metadata.
$fields = [];
$add = static function (string $group, string $prefix, string $source, array $labels) use (&$fields): void {
    foreach ($labels as $name => $label) {
        $fields[$prefix.$name] = ['label' => $label, 'group' => $group, 'source' => $source ? $source.'.'.$name : null, 'type' => 'text'];
    }
};
$add('Venta', '', 'sale', [
    'sale_code' => 'Código de venta', 'sale_date' => 'Fecha contractual', 'sale_type' => 'Tipo de venta',
    'lot_price' => 'Precio de venta', 'initial_payment' => 'Inicial', 'balance_finance' => 'Saldo a financiar',
    'installments_count' => 'Número de cuotas', 'monthly_payment' => 'Cuota mensual', 'interest_rate' => 'Tasa de interés',
    'first_payment_date' => 'Primer vencimiento', 'payment_day' => 'Día de pago', 'status' => 'Estado',
]);
$fields['sale_id'] = ['label' => 'ID de venta (documento)', 'group' => 'Venta', 'source' => 'sale.id', 'type' => 'text'];
$add('Comprador', '', 'customer', [
    'person_type' => 'Tipo de persona', 'first_name' => 'Nombres', 'last_name' => 'Apellidos', 'business_name' => 'Razón social',
    'full_name' => 'Nombre completo', 'document_type' => 'Tipo de documento', 'document_number' => 'Número de documento',
    'ruc' => 'RUC', 'phone' => 'Teléfono', 'email' => 'Correo', 'address' => 'Dirección', 'district' => 'Distrito',
    'province' => 'Provincia', 'department' => 'Departamento', 'ubigeo' => 'Ubigeo', 'gender' => 'Género',
]);
$add('Tratamiento y firma', '', '', [
    'buyer_article' => 'Artículo', 'buyer_role' => 'Tratamiento completo (El Comprador / La Compradora)',
    'buyer_nationality' => 'Nacionalidad', 'buyer_identified' => 'Identificado/a', 'buyer_marital_status' => 'Estado civil',
    'buyer_informed' => 'Enterado/a', 'buyer_obliged' => 'Obligado/a',
    'buyer_signature_name' => 'Nombre en firma', 'buyer_signature_document_type' => 'Documento en firma',
    'buyer_signature_document_number' => 'Número en firma',
]);
$add('Empresa vendedora', 'company_', 'company', [
    'business_name' => 'Razón social', 'trade_name' => 'Nombre comercial', 'ruc' => 'RUC', 'address' => 'Dirección', 'phone' => 'Teléfono', 'email' => 'Correo',
]);
$add('Empresa vendedora', 'representative_', '', [
    'name' => 'Representante', 'document' => 'DNI del representante', 'registry_entry' => 'Partida del representante',
]);
$add('Proyecto', 'project_', 'project', [
    'name' => 'Nombre del proyecto', 'code' => 'Código', 'address' => 'Dirección', 'district' => 'Distrito', 'province' => 'Provincia',
    'department' => 'Departamento', 'total_area' => 'Área total', 'registry_number' => 'Partida registral',
]);
$add('Predio matriz · completar manualmente', 'matrix_', '', [
    'property_name' => 'Nombre del predio matriz', 'sector' => 'Sector', 'address' => 'Ubicación específica',
    'district' => 'Distrito del predio', 'province' => 'Provincia del predio', 'department' => 'Departamento del predio',
    'total_area' => 'Área del predio', 'total_area_words' => 'Área en letras', 'registry_number' => 'Partida del predio',
]);
$lotFields = ['block_name' => 'Manzana', 'lot_number' => 'Número de lote', 'lot_code' => 'Código de lote', 'lot_area' => 'Área', 'lot_unit_measure' => 'Unidad de medida'];
$lotSources = ['block_name' => 'block.name', 'lot_number' => 'number', 'lot_code' => 'code', 'lot_area' => 'area', 'lot_unit_measure' => 'unit_measure'];
foreach ($lotFields as $name => $label) {
    $fields[$name] = ['label' => $label, 'group' => 'Lote principal', 'source' => 'lot.'.$lotSources[$name], 'type' => 'text'];
}
$add('Lotes de la venta', '', '', ['lots_summary' => 'Resumen de todos los lotes']);
$fields['lots_summary']['type'] = 'textarea';
$add('Importes en letras', '', '', ['lot_price_words' => 'Precio en letras', 'initial_payment_words' => 'Inicial en letras', 'balance_finance_words' => 'Saldo en letras']);
$add('Depósitos de la inicial · completar manualmente', 'initial_payment_', '', [
    'deposit_count' => 'Cantidad de depósitos', 'bank' => 'Banco', 'account' => 'Cuenta', 'dates_text' => 'Fechas de depósitos',
]);
$add('Fechas y referencias', '', '', [
    'sale_date_day' => 'Día de firma', 'sale_date_month' => 'Mes de firma (número)', 'sale_date_month_name' => 'Mes de firma',
    'sale_date_year' => 'Año de firma', 'last_installment_due_date' => 'Último vencimiento / gastos de trámite',
    'installment_clause_numerals' => 'Numerales de cuotas de la cláusula tercera',
]);
$scheduleSlots = 49;
for ($i = 1; $i <= $scheduleSlots; $i++) {
    $n = sprintf('%02d', $i);
    $add('Cuota '.$n, 'schedule_'.$n.'_', 'schedules.'.($i - 1), [
        'installment_number' => 'Número de cuota', 'installment_amount' => 'Importe',
        'due_date' => 'Vencimiento',
    ]);
    $fields['schedule_'.$n.'_installment_amount_words'] = ['label' => 'Importe en letras', 'group' => 'Cuota '.$n, 'source' => null, 'type' => 'text'];
    $add('Cuota '.$n, 'bill_'.$n.'_', '', ['amount' => 'Importe de letra', 'amount_words' => 'Letra en palabras', 'number' => 'Número de letra']);
    $fields['schedule_'.$n.'_installment_amount']['type'] = 'money';
    $fields['bill_'.$n.'_amount']['type'] = 'money';
    $fields['schedule_'.$n.'_due_date']['type'] = 'date';
}
foreach (['lot_price', 'initial_payment', 'balance_finance', 'monthly_payment'] as $key) {
    $fields[$key]['type'] = 'money';
}
foreach (['sale_date', 'first_payment_date', 'last_installment_due_date'] as $key) {
    $fields[$key]['type'] = 'date';
}
$fields['gender']['type'] = 'gender';

return [
    'schedule_slots' => $scheduleSlots,
    'templates' => [
        'farje' => [
            'ruc' => '20610686665',
            'name' => 'FARJE',
            'filename_suffix' => 'FARJE',
            'path' => 'app/private/contracts/templates/farje/contrato_compra_venta_lote_farje_parametrizado.docx',
            'max_installments' => 36,
            'installment_limit_message' => 'La plantilla de FARJE admite hasta 36 cuotas.',
            'independent_bills' => false,
            'aliases' => [
                'initial_payment_dates' => 'initial_payment_dates_text',
                'bank_name' => 'initial_payment_bank',
                'bank_account_number' => 'initial_payment_account',
                'buyer_obligated' => 'buyer_obliged',
            ],
            'defaults' => [
                'representative_name' => 'KENNY JOSUE FARJE PULACHE',
                'representative_document' => '74723182',
                'representative_registry_entry' => '11175694',
            ],
            'warning' => 'La plantilla conserva vouchers, cuentas bancarias gráficas y plano fijos. Revise el documento antes de firmarlo.',
        ],
        'barrio_fino' => [
            'ruc' => '20615005917',
            'name' => 'Barrio Fino',
            'filename_suffix' => 'Barrio_Fino',
            'path' => 'app/private/contracts/templates/barrio-fino/contrato_compra_venta_lote_barrio_fino_parametrizado.docx',
            'max_installments' => 35,
            'max_bills' => 35,
            'installment_limit_message' => 'La plantilla de Barrio Fino admite hasta 35 cuotas.',
            'price_words_include_currency' => true,
            'defaults' => [
                'representative_name' => '',
                'representative_document' => '',
                'representative_registry_entry' => '',
            ],
            'warning' => 'La plantilla conserva vouchers, plano y textos legales fijos. Revise el documento antes de firmarlo.',
        ],
        'grupo_krea' => [
            'ruc' => '20607752312',
            'name' => 'Grupo Krea',
            'filename_suffix' => 'Grupo_Krea',
            'path' => 'app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea_parametrizado.docx',
            'max_installments' => 49,
            'max_bills' => 49,
            'installment_limit_message' => 'La plantilla de Grupo Krea admite hasta 49 cuotas. Revise el contrato o la plantilla.',
            'defaults' => [
                'representative_name' => 'KENNY JOSUE FARJE PULACHE',
                'representative_document' => '74723182',
                'representative_registry_entry' => '11175694',
            ],
            'warning' => 'La plantilla conserva vouchers y plano fijos. Sus cláusulas segunda y siguientes describen un solo lote; para una venta múltiple revise y adapte el documento antes de firmarlo. El resumen de lotes está disponible en el formulario, pero esta plantilla no contiene un espacio para insertarlo.',
        ],
    ],
    'fields' => $fields,
    'lot_fields' => $lotFields,
    'lot_sources' => $lotSources,
    'gender_defaults' => [
        'femenino' => ['buyer_article' => 'La', 'buyer_role' => 'La Compradora', 'buyer_nationality' => 'peruana', 'buyer_identified' => 'identificada', 'buyer_informed' => 'enterada', 'buyer_obliged' => 'obligada'],
        'masculino' => ['buyer_article' => 'El', 'buyer_role' => 'El Comprador', 'buyer_nationality' => 'peruano', 'buyer_identified' => 'identificado', 'buyer_informed' => 'enterado', 'buyer_obliged' => 'obligado'],
    ],
];
