<?php

// Generates a synthetic QA document and a complete variable/source inventory. No DB access.
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$config = require $root.'/config/contracts.php';
$directory = $root.'/storage/app/private/contracts/templates/grupo-krea';
$report = json_decode(file_get_contents($directory.'/parametrizacion-auditoria.json'), true, 512, JSON_THROW_ON_ERROR);
$template = $directory.'/contrato_compra_venta_lote_grupo_krea_parametrizado.docx';
$zip = new ZipArchive;
$zip->open($template);
$media = [];
$entries = [];
for ($i = 0; $i < $zip->numFiles; $i++) $entries[] = $zip->getNameIndex($i);
foreach ($entries as $name) {
    if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
        $dom = new DOMDocument;
        if (!$dom->loadXML($zip->getFromName($name))) throw new RuntimeException('XML inválido: '.$name);
        if (str_ends_with($name, '.rels')) {
            foreach ($dom->getElementsByTagName('Relationship') as $rel) {
                if ($rel->getAttribute('TargetMode') === 'External') continue;
                $target = $rel->getAttribute('Target');
                $base = $name === '_rels/.rels' ? '' : dirname(dirname($name)).'/';
                $parts = [];
                foreach (explode('/', str_starts_with($target, '/') ? substr($target, 1) : $base.$target) as $part) {
                    if ($part === '..') array_pop($parts);
                    elseif ($part !== '.' && $part !== '') $parts[] = $part;
                }
                if (!in_array(implode('/', $parts), $entries, true)) throw new RuntimeException('Relación rota: '.$target);
            }
        }
        if (str_starts_with($name, 'word/') && str_ends_with($name, '.xml')) {
            preg_match_all('/\$\{([^}]+)\}/', $dom->textContent, $all);
            preg_match_all('/\$\{([^}]+)\}/', $zip->getFromName($name), $continuous);
            if ($all[1] !== $continuous[1]) throw new RuntimeException('Marcador dividido entre runs: '.$name);
        }
    }
}
$zip->close();
$lines = [
    '# Contratos de Grupo Krea', '',
    '## Plantillas', '',
    '- Original intacto: `storage/app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea.docx`.',
    '- Copia utilizada: `storage/app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea_parametrizado.docx`.',
    '- SHA-256 original: `'.$report['original_sha256'].'`.',
    '- Imágenes: 8 antes y 8 después, contenido binario idéntico. Vouchers, plano, encabezados, pies, estilos y relaciones intactos.',
    '- Solo se modificó texto en `word/document.xml`; los demás miembros ZIP son idénticos al original.',
    '- Word abrió la copia en modo de solo lectura sin reparación. Los marcadores largos pueden aumentar la paginación de la plantilla; revisar el Word final con los valores definitivos.',
    '', '## Alcance de la plantilla', '',
    'La plantilla conserva la redacción singular de las cláusulas segunda y siguientes y su plano fijo. El formulario carga todos los sale_lots y ofrece un resumen, pero no se agregó un espacio nuevo ni se adaptó la redacción para múltiples lotes. Es necesario revisar/adaptar ese documento antes de firmar una venta múltiple.',
    '', 'Los campos del catálogo sin un espacio equivalente en el original se ofrecen como datos de apoyo y no se insertan artificialmente en el texto. Incluyen teléfono/correo de empresa, datos separados de nombres/apellidos, saldo, inicial en letras, código del lote, resumen de lotes y número/palabras de cuota. Las letras sí tienen sus importes, palabras y números. El formulario identifica los campos sin espacio.',
    '', 'El predio matriz es independiente del proyecto: sus campos se dejan manuales y vacíos para no atribuirle datos sin confirmar. Las penalidades y gastos fijos de las cláusulas se conservaron.',
    '', '## Variables reales y fuentes', '',
    'Total: '.count($report['variables']).' variables únicas. Cada marcador está íntegro dentro de un único nodo de texto.', '',
    '| Variable | Fuente / valor inicial |', '|---|---|',
];
$derived = [
    'buyer_article' => 'Customer.gender; editable', 'buyer_role' => 'Customer.gender: La Compradora / El Comprador; editable',
    'buyer_nationality' => 'Customer.gender: peruana / peruano; editable', 'buyer_identified' => 'Customer.gender; editable',
    'buyer_informed' => 'Customer.gender; editable', 'buyer_obliged' => 'Customer.gender; editable',
    'buyer_signature_name' => 'customer.full_name', 'buyer_signature_document_type' => 'customer.document_type',
    'buyer_signature_document_number' => 'customer.document_number', 'lot_price_words' => 'sale.lot_price → AmountToWordsService',
    'last_installment_due_date' => 'Última cuota real ordenada por installment_number, id',
    'installment_clause_numerals' => 'Posiciones de las cuotas en cláusula tercera (la inicial ocupa la primera)',
    'sale_date_day' => 'sale.sale_date → día', 'sale_date_month_name' => 'sale.sale_date → mes en español', 'sale_date_year' => 'sale.sale_date → año',
];
\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
$processor = new \PhpOffice\PhpWord\TemplateProcessor($template);
$sample = [
    'full_name' => 'CLIENTE DE PRUEBA', 'buyer_signature_name' => 'CLIENTE DE PRUEBA',
    'document_type' => 'DNI', 'buyer_signature_document_type' => 'DNI', 'document_number' => '00000000', 'buyer_signature_document_number' => '00000000',
    'company_business_name' => 'EMPRESA DE PRUEBA S.A.C.', 'company_ruc' => '20607752312',
    'company_address' => 'DIRECCIÓN DE PRUEBA 123', 'address' => 'DIRECCIÓN DE PRUEBA 456',
    'district' => 'Tarapoto', 'province' => 'San Martín', 'department' => 'San Martín',
    'block_name' => 'B', 'lot_number' => '08', 'lot_area' => '178.79', 'lot_unit_measure' => 'm²',
    'project_name' => 'PROYECTO DE PRUEBA', 'lot_price' => '51,113.28', 'lot_price_words' => 'Cincuenta y Un Mil Ciento Trece con 28/100',
    'initial_payment' => '1,113.28', 'initial_payment_deposit_count' => '2', 'initial_payment_account' => '0000000000',
    'initial_payment_bank' => 'BANCO DE PRUEBA', 'initial_payment_dates_text' => '24/07/2026',
    'installments_count' => '36', 'buyer_marital_status' => 'soltera', 'sale_date_day' => '24', 'sale_date_month_name' => 'julio', 'sale_date_year' => '2026',
    'matrix_property_name' => 'PREDIO DE PRUEBA', 'matrix_sector' => 'SECTOR DE PRUEBA', 'matrix_district' => 'Tarapoto',
    'matrix_province' => 'San Martín', 'matrix_department' => 'San Martín', 'matrix_total_area' => '10000',
    'matrix_total_area_words' => 'Diez mil', 'matrix_registry_number' => '00000000', 'last_installment_due_date' => '05/09/2029',
    'installment_clause_numerals' => '2 al 37',
] + $config['templates']['grupo_krea']['defaults'] + $config['gender_defaults']['femenino'];
foreach ($report['variables'] as $key) {
    $source = $config['fields'][$key]['source'] ?? null;
    if (isset($config['templates']['grupo_krea']['defaults'][$key])) $source = 'Default de plantilla; editable: '.$config['templates']['grupo_krea']['defaults'][$key];
    $source = $derived[$key] ?? $source;
    if (preg_match('/^bill_(\d+)_(amount|amount_words|number)$/', $key, $m)) {
        $source = 'payment_schedules cuota '.(int) $m[1].' → '.($m[2] === 'number' ? 'installment_number' : 'installment_amount'.($m[2] === 'amount_words' ? ' → letras' : ''));
        $sample[$key] = $m[2] === 'number' ? $m[1] : ($m[2] === 'amount' ? '700.00' : 'Setecientos con 00/100 Soles');
    }
    if (preg_match('/^schedule_(\d+)_(installment_amount|due_date)$/', $key, $m)) {
        $sample[$key] = $m[2] === 'due_date' ? '05/09/2026' : '700.00';
    }
    $lines[] = '| `${'.$key.'}` | '.($source ?: 'Manual, inicialmente vacío').' |';
    $processor->setValue($key, $sample[$key] ?? '');
}
$qa = $root.'/storage/app/private/contracts/qa';
if (!is_dir($qa)) mkdir($qa, 0700, true);
$processor->saveAs($qa.'/contrato_prueba.docx');
if (!is_dir($root.'/docs')) mkdir($root.'/docs');
file_put_contents($root.'/docs/contratos-grupo-krea.md', implode("\n", $lines)."\n");
echo 'ZIP/XML, relaciones y marcadores continuos: OK. Variables: '.count($report['variables']).PHP_EOL;
echo 'Copia de prueba sintética: '.$qa.'/contrato_prueba.docx'.PHP_EOL;
