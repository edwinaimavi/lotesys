@php
    $unit = ($lot['unit_measure'] ?? null) === 'm2' ? 'm²' : (($lot['unit_measure'] ?? null) ?: 'm²');
    $blockLabel = trim((string)($lot['block'] ?? ''));
    $blockLabel = preg_match('/^mz\b/i', $blockLabel) ? preg_replace('/^mz\b/i', 'MANZANA', $blockLabel) : (preg_match('/^manzana\b/i', $blockLabel) ? mb_strtoupper($blockLabel) : 'MANZANA '.$blockLabel);
@endphp
<article class="inventory-lot-card">
    <div class="inventory-lot-top"><span>{{ $blockLabel }}</span><span class="inventory-lot-status"><i></i>Disponible</span></div>
    @if(!empty($lot['project']))<p class="inventory-lot-project">{{ $lot['project'] }}</p>@endif
    @if(!empty($lot['location']))<p class="inventory-lot-location">{{ $lot['location'] }}</p>@endif
    <h3>Lote {{ $lot['lot_number'] }}</h3>
    @if(!empty($lot['code']))<span class="inventory-lot-code">{{ $lot['code'] }}</span>@endif
    <dl class="inventory-lot-facts">
        <div><dt>Área</dt><dd>{{ $lot['area'] !== null ? rtrim(rtrim(number_format((float)$lot['area'], 2, '.', ','), '0'), '.').' '.$unit : 'Consultar' }}</dd></div>
        <div><dt>Precio contado</dt><dd>{{ $lot['cash_price'] !== null ? 'S/ '.number_format((float)$lot['cash_price'], 2, '.', ',') : 'Consultar' }}</dd></div>
    </dl>
    @if(!empty($lot['whatsapp_url']))<a class="inventory-lot-contact" href="{{ $lot['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer">Me interesa este lote <span>→</span></a>@endif
</article>
