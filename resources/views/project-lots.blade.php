@php
    $profile = $project->webProfile;
    $phone = config('landing.phone');
    $email = config('landing.email');
    $whatsapp = $primaryWhatsapp ?? preg_replace('/\D/', '', config('landing.whatsapp', ''));
    $contactUrl = $whatsapp
        ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero información sobre el proyecto '.$project->name.'.')
        : ($email ? 'mailto:'.$email : url('/#contacto'));
    $location = collect([$project->district, $project->province, $project->department])
        ->filter()
        ->unique(fn ($value) => mb_strtolower($value))
        ->join(' · ');
    $coverUrl = $profile?->cover_image_url ?: $profile?->plan_image_url;
    $activeFilters = collect($filters ?? [])->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Consulta los lotes disponibles de {{ $project->name }} y encuentra una opción según manzana, precio y área.">
    <meta name="theme-color" content="#244b38">
    <title>{{ $project->name }} | Lotes disponibles | Grupo Krea</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/krea-landing.css') }}">
</head>
<body class="inventory-page">
<div class="topline"><div class="container"><span>Construimos oportunidades. Creamos futuro.</span><a href="{{ $phone ? 'tel:'.preg_replace('/[^+0-9]/', '', $phone) : url('/#contacto') }}">{{ $phone ?: 'Conversemos sobre tu próximo lote' }} ↗</a></div></div>
<header class="inventory-header">
    <div class="container inventory-nav">
        <a class="brand" href="{{ url('/') }}" aria-label="Grupo Krea, inicio"><img src="{{ asset(config('landing.logo')) }}" width="46" height="46" alt=""><span>GRUPO <strong>KREA</strong><small>DESARROLLAMOS TU FUTURO</small></span></a>
        <div class="inventory-nav-actions"><a href="{{ url('/#proyectos') }}">Proyectos</a><a class="button button-small" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Hablar con un asesor ↗</a></div>
    </div>
</header>
<main>
    <section class="inventory-hero">
        @if($coverUrl)<img class="inventory-hero-image" src="{{ $coverUrl }}" alt="{{ $profile?->image_alt ?: $project->name }}">@endif
        <div class="inventory-hero-overlay"></div>
        <div class="container inventory-hero-content">
            <a class="inventory-back" href="{{ url('/#disponibilidad') }}">← Volver a proyectos</a>
            <span class="inventory-badge">{{ $profile?->badge_text ?: $profile?->commercial_status_label }}</span>
            <span class="eyebrow light">INVENTARIO DISPONIBLE</span>
            <h1>{{ $project->name }}</h1>
            <p>{{ $profile?->short_description ?: 'Explora la disponibilidad actual y encuentra el lote que mejor se adapte a tus planes.' }}</p>
            <div class="inventory-hero-meta">
                @if($project->company?->business_name)<span>{{ $project->company->business_name }}</span>@endif
                @if($location)<span>{{ $location }}</span>@endif
            </div>
        </div>
    </section>

    <section class="container inventory-summary" aria-label="Resumen de disponibilidad">
        <div><small>DISPONIBLES</small><strong>{{ $totalAvailable }}</strong><span>{{ $totalAvailable === 1 ? 'lote' : 'lotes' }}</span></div>
        <div><small>PRECIO DESDE</small><strong>{{ $minimumPrice !== null ? 'S/ '.number_format((float) $minimumPrice, 2, '.', ',') : '—' }}</strong><span>precio contado</span></div>
        <div><small>ÁREA DESDE</small><strong>{{ $minimumArea !== null ? rtrim(rtrim(number_format((float) $minimumArea, 2, '.', ','), '0'), '.').' m²' : '—' }}</strong><span>según disponibilidad</span></div>
    </section>

    <section class="inventory-catalog">
        <div class="container">
            <div class="inventory-heading">
                <div><span class="eyebrow">ENCUENTRA TU LOTE</span><h2>Disponibilidad de {{ $project->name }}</h2><p>Filtra el inventario real por manzana, precio o área.</p></div>
                <span class="inventory-result-count">{{ $lots->total() }} {{ $lots->total() === 1 ? 'resultado' : 'resultados' }}</span>
            </div>

            <form class="inventory-filters" method="GET" action="{{ route('public.projects.lots', $project) }}">
                <label><span>MANZANA</span><select name="block_id"><option value="">Todas</option>@foreach($blocks as $block)<option value="{{ $block->id }}" @selected((string)($filters['block_id'] ?? '') === (string)$block->id)>{{ $block->name }}</option>@endforeach</select></label>
                <label><span>PRECIO MÍNIMO</span><input type="number" name="min_price" min="0" step="0.01" value="{{ $filters['min_price'] ?? '' }}" placeholder="S/ 0"></label>
                <label><span>PRECIO MÁXIMO</span><input type="number" name="max_price" min="0" step="0.01" value="{{ $filters['max_price'] ?? '' }}" placeholder="Sin límite"></label>
                <label><span>ÁREA MÍNIMA</span><input type="number" name="min_area" min="0" step="0.01" value="{{ $filters['min_area'] ?? '' }}" placeholder="m²"></label>
                <label><span>ÁREA MÁXIMA</span><input type="number" name="max_area" min="0" step="0.01" value="{{ $filters['max_area'] ?? '' }}" placeholder="m²"></label>
                <div class="inventory-filter-actions"><button class="button" type="submit">Aplicar filtros</button>@if($activeFilters)<a class="inventory-clear" href="{{ route('public.projects.lots', $project) }}">Limpiar</a>@endif</div>
            </form>

            @if($errors->any())
                <div class="inventory-alert" role="alert">Revisa los filtros ingresados e inténtalo nuevamente.</div>
            @endif

            @if($lots->count())
                <div class="inventory-grid">
                    @foreach($lots as $lot)
                        @php
                            $unit = ($lot['unit_measure'] ?? null) === 'm2' ? 'm²' : (($lot['unit_measure'] ?? null) ?: 'm²');
                            $blockLabel = trim((string)($lot['block'] ?? ''));
                            $blockLabel = preg_match('/^mz\b/i', $blockLabel) ? preg_replace('/^mz\b/i', 'MANZANA', $blockLabel) : (preg_match('/^manzana\b/i', $blockLabel) ? mb_strtoupper($blockLabel) : 'MANZANA '.$blockLabel);
                        @endphp
                        <article class="inventory-lot-card">
                            <div class="inventory-lot-top"><span>{{ $blockLabel }}</span><span class="inventory-lot-status"><i></i>Disponible</span></div>
                            <h3>Lote {{ $lot['lot_number'] }}</h3>
                            @if(!empty($lot['code']))<span class="inventory-lot-code">{{ $lot['code'] }}</span>@endif
                            <dl class="inventory-lot-facts">
                                <div><dt>Área</dt><dd>{{ $lot['area'] !== null ? rtrim(rtrim(number_format((float)$lot['area'], 2, '.', ','), '0'), '.').' '.$unit : 'Consultar' }}</dd></div>
                                <div><dt>Precio contado</dt><dd>{{ $lot['cash_price'] !== null ? 'S/ '.number_format((float)$lot['cash_price'], 2, '.', ',') : 'Consultar' }}</dd></div>
                            </dl>
                            @if(!empty($lot['whatsapp_url']))<a class="inventory-lot-contact" href="{{ $lot['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer">Me interesa este lote <span>→</span></a>@endif
                        </article>
                    @endforeach
                </div>

                @if($lots->lastPage() > 1)
                    @php
                        $startPage = max(1, $lots->currentPage() - 2);
                        $endPage = min($lots->lastPage(), $lots->currentPage() + 2);
                    @endphp
                    <nav class="inventory-pagination" aria-label="Paginación de lotes">
                        @if($lots->onFirstPage())<span class="is-disabled">← Anterior</span>@else<a href="{{ $lots->previousPageUrl() }}">← Anterior</a>@endif
                        <div class="inventory-pages">
                            @for($page = $startPage; $page <= $endPage; $page++)
                                @if($page === $lots->currentPage())<span class="is-current" aria-current="page">{{ $page }}</span>@else<a href="{{ $lots->url($page) }}">{{ $page }}</a>@endif
                            @endfor
                        </div>
                        @if($lots->hasMorePages())<a href="{{ $lots->nextPageUrl() }}">Siguiente →</a>@else<span class="is-disabled">Siguiente →</span>@endif
                    </nav>
                @endif
            @else
                <div class="inventory-empty">
                    <span class="eyebrow">SIN COINCIDENCIAS</span>
                    <h3>No encontramos lotes con esos filtros.</h3>
                    <p>Puedes limpiar los filtros o consultar directamente con un asesor.</p>
                    <div><a class="button button-outline" href="{{ route('public.projects.lots', $project) }}">Limpiar filtros</a><a class="button" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Hablar con un asesor →</a></div>
                </div>
            @endif
        </div>
    </section>
</main>
<footer class="inventory-footer"><div class="container"><span>© {{ date('Y') }} Grupo Krea.</span><a href="{{ url('/') }}">Volver al inicio ↑</a></div></footer>
</body>
</html>
