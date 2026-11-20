@php
    $whatsapp = $primaryWhatsapp;
    $contactUrl = $whatsapp
        ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero información sobre los lotes disponibles.')
        : url('/#contacto');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Encuentra lotes disponibles por ubicación, proyecto y presupuesto en Grupo Krea.">
    <meta name="theme-color" content="#244b38">
    <title>Lotes disponibles | Grupo Krea</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/krea-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/krea-editorial.css') }}">
</head>
<body class="inventory-page">
<div class="topline"><div class="container"><span>Construimos oportunidades. Creamos futuro.</span><a href="{{ url('/#contacto') }}">Conversemos sobre tu próximo lote ↗</a></div></div>
<header class="inventory-header">
    <div class="container inventory-nav">
        <a class="brand" href="{{ url('/') }}" aria-label="Grupo Krea, inicio"><img src="{{ asset(config('landing.logo')) }}" width="46" height="46" alt=""><span>GRUPO <strong>KREA</strong><small>PIENSA EN GRANDE</small></span></a>
        <div class="inventory-nav-actions"><a href="{{ url('/#proyectos') }}">Proyectos</a><a class="button button-small" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Hablar con un asesor ↗</a></div>
    </div>
</header>
<main>
    <section class="inventory-hero">
        <div class="inventory-hero-overlay"></div>
        <div class="container inventory-hero-content">
            <a class="inventory-back" href="{{ url('/') }}">← Volver al inicio</a>
            <span class="eyebrow light">ENCUENTRA TU PRÓXIMO LOTE</span>
            <h1>Lotes disponibles</h1>
            <p>{{ $lots->total() }} oportunidades encontradas</p>
        </div>
    </section>
    <section class="inventory-catalog">
        <div class="container">
            <div class="inventory-heading"><div><span class="eyebrow">OPORTUNIDADES DISPONIBLES</span><h2>Resultados de tu búsqueda</h2><p>Explora los lotes que se ajustan a tus planes.</p></div><span class="inventory-result-count">Mostrando {{ $lots->count() }} de {{ $lots->total() }} lotes disponibles.</span></div>
            <div class="inventory-applied-filters" aria-label="Filtros aplicados">
                @if(!empty($filters['location']))<span>Ubicación: {{ $filters['location'] }}</span>@endif
                @if($selectedProject)<span>Proyecto: {{ $selectedProject->name }}</span>@endif
                @if(isset($filters['min_price']))<span>Desde S/ {{ number_format((float)$filters['min_price'], 2, '.', ',') }}</span>@endif
                @if(isset($filters['max_price']))<span>Hasta S/ {{ number_format((float)$filters['max_price'], 2, '.', ',') }}</span>@endif
            </div>
            @if($lots->count())
                <div class="inventory-grid">
                    @foreach($lots as $lot)
                        @include('landing.partials.lot-card', ['lot' => $lot])
                    @endforeach
                </div>
                @include('landing.partials.lot-pagination', ['lots' => $lots])
            @else
                <div class="inventory-empty"><span class="eyebrow">SIN COINCIDENCIAS</span><h3>No encontramos lotes con esos filtros.</h3><p>Puedes volver al buscador o consultar directamente con un asesor.</p><div><a class="button button-outline" href="{{ url('/#lot-search') }}">Volver al buscador</a><a class="button" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Hablar con un asesor →</a></div></div>
            @endif
        </div>
    </section>
</main>
<footer class="inventory-footer"><div class="container"><span>© {{ date('Y') }} Grupo Krea.</span><a href="{{ url('/') }}">Volver al inicio ↑</a></div></footer>
</body>
</html>
