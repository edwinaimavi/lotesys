<?php

// Targeted OOXML text edits only. Never saves through Word or reconstructs the package.
$root = dirname(__DIR__, 2);
$original = $root.'/storage/app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea.docx';
$output = str_replace('.docx', '_parametrizado.docx', $original);
$originalHash = hash_file('sha256', $original);
$source = new ZipArchive;
if ($source->open($original) !== true) {
    throw new RuntimeException('No se puede abrir la plantilla original.');
}
$xml = $source->getFromName('word/document.xml');
$audit = [];
$paragraph = -1;
$xml = preg_replace_callback('~<w:p(?:\s[^>]*)?>.*?</w:p>~s', function ($match) use (&$paragraph, &$audit) {
    $paragraph++;
    $p = $match[0];
    $replace = function (string $old, string $new) use (&$p, &$audit, &$paragraph) {
        while (true) {
            preg_match_all('~<w:t(?:\s[^>]*)?>(.*?)</w:t>~s', $p, $matches, PREG_OFFSET_CAPTURE);
            $texts = array_map(fn ($m) => html_entity_decode($m[0], ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1]);
            $text = implode('', $texts);
            $at = strpos($text, $old);
            if ($at === false) {
                break;
            }
            $offset = 0;
            $edits = [];
            foreach ($texts as $i => $value) {
                $end = $offset + strlen($value);
                if ($end > $at && $offset < $at + strlen($old)) {
                    $startInNode = max(0, $at - $offset);
                    $endInNode = min(strlen($value), $at + strlen($old) - $offset);
                    $replacement = substr($value, 0, $startInNode)
                        .($offset <= $at ? $new : '').substr($value, $endInNode);
                    $edits[] = [$matches[1][$i][1], strlen($matches[1][$i][0]), htmlspecialchars($replacement, ENT_QUOTES | ENT_XML1, 'UTF-8')];
                }
                $offset = $end;
            }
            foreach (array_reverse($edits) as [$pos, $length, $value]) {
                $p = substr_replace($p, $value, $pos, $length);
            }
            $audit[] = ['paragraph' => $paragraph, 'original' => $old, 'variable' => $new];
            if ($old === $new) {
                break;
            }
        }
    };
    foreach (['KENNY JOSUE FARJE PULACHE' => '${representative_name}', '74723182' => '${representative_document}', '11175694' => '${representative_registry_entry}', 'La Compradora' => '${buyer_role}', 'El Comprador' => '${buyer_role}'] as $old => $new) {
        $replace($old, $new);
    }
    if ($paragraph === 5) {
        $replace('peruana', '${buyer_nationality}');
        $replace('identificada', '${buyer_identified}');
        $replace('casado(a)', '${buyer_marital_status}');
    }
    if ($paragraph === 9) {
        $replace('VISTA ALEGRE', '${matrix_property_name}');
        foreach (['address' => 'sector', 'district' => 'district', 'province' => 'province', 'department' => 'department', 'total_area' => 'total_area', 'total_area_words' => 'total_area_words', 'registry_number' => 'registry_number'] as $from => $to) {
            $replace('${project_'.$from.'}', '${matrix_'.$to.'}');
        }
    }
    if ($paragraph === 26) {
        $replace('${bank_account_number}', '${initial_payment_account}');
        $replace('${bank_name}', '${initial_payment_bank}');
        $replace('${initial_payment_dates}', '${initial_payment_dates_text}');
    }
    if ($paragraph >= 72 && $paragraph <= 107) {
        $n = sprintf('%02d', $paragraph - 71);
        $replace('${schedule_'.$n.'_installment_amount}', '${bill_'.$n.'_amount}');
        $replace('${schedule_'.$n.'_installment_amount_words}', '${bill_'.$n.'_amount_words}');
        $replace('Letra N°'.$n, 'Letra N°${bill_'.$n.'_number}');
    }
    if ($paragraph === 124) {
        $replace('enterado', '${buyer_informed}');
    }
    if ($paragraph === 126) {
        $replace('obligado', '${buyer_obliged}');
    }
    if ($paragraph === 151) {
        $replace('obligados', '${buyer_obliged}');
    }
    if ($paragraph === 156) {
        $replace('${full_name}', '${buyer_signature_name}');
    }
    if ($paragraph === 157) {
        $replace('${document_type}', '${buyer_signature_document_type}');
        $replace('${document_number}', '${buyer_signature_document_number}');
    }
    // Consolidate split macros while retaining every run and its formatting.
    preg_match_all('/\$\{[^}]+\}/', html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_XML1, 'UTF-8'), $macros);
    foreach (array_unique($macros[0]) as $macro) {
        $replace($macro, $macro);
    }
    return $p;
}, $xml);
$dom = new DOMDocument;
if (!$dom->loadXML($xml)) {
    throw new RuntimeException('XML inválido.');
}
copy($original, $output);
$target = new ZipArchive;
$target->open($output);
$target->addFromString('word/document.xml', $xml);
$target->close();
$target->open($output);
$media = [];
for ($i = 0; $i < $source->numFiles; $i++) {
    $name = $source->getNameIndex($i);
    if ($name !== 'word/document.xml' && $source->getFromName($name) !== $target->getFromName($name)) {
        throw new RuntimeException('Parte alterada: '.$name);
    }
    if (str_starts_with($name, 'word/media/')) {
        $media[$name] = hash('sha256', $target->getFromName($name));
    }
}
preg_match_all('/\$\{([^}]+)\}/', $xml, $vars);
$variables = array_values(array_unique($vars[1]));
sort($variables);
$source->close();
$target->close();
if ($originalHash !== hash_file('sha256', $original)) {
    throw new RuntimeException('Original alterado.');
}
$report = ['original' => $original, 'original_sha256' => $originalHash, 'parametrized' => $output, 'images_before' => count($media), 'images_after' => count($media), 'media_sha256' => $media, 'unchanged_parts' => 'Todas excepto word/document.xml; relaciones idénticas.', 'variables' => $variables, 'replacements' => $audit];
file_put_contents(dirname($output).'/parametrizacion-auditoria.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode(['variables' => $variables, 'images' => count($media), 'original_sha256' => $originalHash], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
