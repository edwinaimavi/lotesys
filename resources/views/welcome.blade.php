@php
    $homeProjects = $homeProjects ?? collect();
    $availabilityProjects = $availabilityProjects ?? collect();
    $lotSearchProjects = $lotSearchProjects ?? collect();
    $lotSearchLocations = $lotSearchLocations ?? collect();
    $lotSearchPriceRanges = $lotSearchPriceRanges ?? [];
    $heroSlides = $heroSlides ?? config('landing.hero_slides', []);
    $phone = config('landing.phone');
    $email = config('landing.email');
    $whatsapp = $primaryWhatsapp ?? preg_replace('/\D/', '', config('landing.whatsapp', ''));
    $landingContacts = $landingContacts ?? app(\App\Services\LandingContacts::class)->footer();
    $contactUrl = $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, quiero información sobre sus proyectos.') : ($email ? 'mailto:'.$email : '#contacto');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Encuentra un espacio para tu futuro con Grupo Krea. Explora nuestros proyectos de lotes y terrenos en Perú.">
    <meta name="theme-color" content="#244b38">
    <title>Grupo Krea | Un lugar para tu futuro</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/krea-landing.css') }}">
    <script src="{{ asset('js/krea-landing.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/krea-customer-portal.css') }}">
    <script src="{{ asset('js/krea-customer-portal.js') }}" defer></script>
</head>
<body>
<svg class="svg-sprite" aria-hidden="true" focusable="false">
    <symbol id="icon-phone" viewBox="0 0 24 24"><path d="M7.2 3.2 9.8 7a1.5 1.5 0 0 1-.2 1.9l-1.3 1.3a15.5 15.5 0 0 0 5.5 5.5l1.3-1.3a1.5 1.5 0 0 1 1.9-.2l3.8 2.6a1.5 1.5 0 0 1 .6 1.7l-.6 2.1a2 2 0 0 1-1.9 1.4C9.6 22 2 14.4 2 5.1a2 2 0 0 1 1.4-1.9l2.1-.6a1.5 1.5 0 0 1 1.7.6Z"/></symbol>
    <symbol id="icon-mail" viewBox="0 0 24 24"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m4 7 8 6 8-6"/></symbol>
    <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="icon-whatsapp" viewBox="0 0 24 24"><path d="M20.5 11.8a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.3-4.7a8.5 8.5 0 1 1 16.2-4Z"/><path d="M8.2 7.6c.2-.5.4-.5.8-.5h.4c.2 0 .4 0 .6.5l.8 2c.1.3.1.5-.1.7l-.7.8c-.2.2-.3.4-.1.7.7 1.4 1.7 2.5 3.2 3.2.3.2.5.1.7-.1l.9-1.1c.2-.3.4-.3.7-.2l2.1 1c.3.1.5.2.5.4 0 .2-.1 1.3-.6 1.8-.5.6-1.3 1-2.2 1-1.3 0-3.5-.7-5.4-2.4-2.4-2.1-3.8-4.7-3.8-6.3 0-.8.3-1.4.6-1.8.4-.4.8-.6 1.2-.6"/></symbol>
    <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="icon-facebook" viewBox="0 0 24 24"><path class="icon-fill" d="M13.7 22v-8h2.7l.4-3.1h-3.1v-2c0-.9.3-1.5 1.6-1.5H17V4.6c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.1H7.5V14h2.8v8h3.4Z"/></symbol>
    <symbol id="icon-instagram" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle class="icon-fill" cx="17.4" cy="6.7" r="1"/></symbol>
    <symbol id="icon-tiktok" viewBox="0 0 24 24"><path d="M14.2 3v11.4a4.5 4.5 0 1 1-3.7-4.4v3.2a1.5 1.5 0 1 0 .7 1.2V3h3Zm0 0c.5 2.8 2.1 4.4 4.8 4.8v3.1a8.5 8.5 0 0 1-4.8-1.6"/></symbol>
</svg>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="topline"><div class="container"><span>Construimos oportunidades. Creamos futuro.</span><a href="{{ $phone ? 'tel:'.preg_replace('/[^+0-9]/', '', $phone) : '#contacto' }}">{{ $phone ?: 'Conversemos sobre tu próximo lote' }} ↗</a></div></div>
<header class="header"><div class="container nav-shell">
    <a class="brand" href="#inicio" aria-label="Grupo Krea, inicio"><img src="{{ asset(config('landing.logo')) }}" width="46" height="46" alt=""><span>GRUPO <strong>KREA</strong><small>DESARROLLAMOS TU FUTURO</small></span></a>
    <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav">☰</button>
    <nav id="main-nav" aria-label="Navegación principal"><a href="#inicio">Inicio</a><a href="#proyectos">Proyectos</a><a href="#nosotros">Nosotros</a><a href="#invertir">Por qué invertir</a><a href="#blog">Blog</a><a href="#contacto">Contacto</a></nav>
    <a class="button button-small nav-cta" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Quiero más información ↗</a>
</div></header>
<main id="contenido">
<section class="hero hero-slider" id="inicio" data-hero-slider aria-roledescription="carrusel" aria-label="Propuestas de Grupo Krea">
    <div class="hero-slides">
        @foreach($heroSlides as $slide)
            @php
                $slideUrl = $slide['button_url'] ?? (($slide['action'] ?? null) === 'contact' ? $contactUrl : '#proyectos');
                $isExternal = str_starts_with($slideUrl, 'http');
                $imageUrl = $slide['image_url'] ?? asset($slide['image']);
                $mobileImageUrl = $slide['mobile_image_url'] ?? null;
            @endphp
            <article class="hero-slide{{ $loop->first ? ' is-active' : '' }}" data-hero-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @unless($loop->first) inert @endunless>
                <picture>
                    @if($mobileImageUrl)<source media="(max-width: 760px)" srcset="{{ $mobileImageUrl }}">@endif
                    <img class="hero-slide-image{{ $slide['crop'] ? ' is-cropped' : '' }}{{ $mobileImageUrl ? ' has-mobile-image' : '' }}" src="{{ $imageUrl }}" alt="{{ $slide['image_alt'] ?? '' }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                </picture>
                <div class="container hero-slide-content">
                    <div class="hero-slide-copy">
                        <span class="eyebrow light">{{ $slide['eyebrow'] }}</span>
                        <h1>@if(!empty($slide['title_accent'])){{ $slide['title_lead'] }} <em>{{ $slide['title_accent'] }}</em>@else{{ $slide['title'] }}@endif</h1>
                        <p>{{ $slide['text'] }}</p>
                        @if(!empty($slide['button']) && !empty($slideUrl))<a class="button" href="{{ $slideUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>{{ $slide['button'] }} <span aria-hidden="true">↗</span></a>@endif
                    </div>
                    <div class="hero-note"><span></span>{{ str_replace('. ', ".\n", $slide['support'] ?? '') }}</div>
                </div>
            </article>
        @endforeach
    </div>
    <div class="container hero-controls" aria-label="Controles del carrusel">
        <button type="button" class="hero-control" data-hero-prev aria-label="Ver slide anterior">←</button>
        <span class="hero-counter" aria-live="polite"><strong data-hero-current>01</strong><i></i><span>{{ str_pad(count($heroSlides), 2, '0', STR_PAD_LEFT) }}</span></span>
        <button type="button" class="hero-control" data-hero-next aria-label="Ver slide siguiente">→</button>
    </div>
    <span class="image-caption">Imágenes referenciales</span>
</section>
<div class="container search-wrap" data-reveal="up" style="--reveal-delay:280ms"><form class="search-panel" id="lot-search" action="{{ route('public.lots.search') }}" data-price-ranges-url="{{ route('public.lots.price-ranges') }}" method="GET">
    <label><span>01 / UBICACIÓN</span><select id="location" name="location"><option value="">Todas las ubicaciones</option>@foreach($lotSearchLocations as $location)<option value="{{ $location }}">{{ $location }}</option>@endforeach</select></label>
    <label><span>02 / PROYECTO</span><select id="project-filter" name="project_id"><option value="">Todos los proyectos</option>@foreach($lotSearchProjects as $project)<option value="{{ $project['id'] }}" data-location="{{ $project['location'] }}">{{ $project['name'] }}</option>@endforeach</select></label>
    <label><span>03 / PRESUPUESTO</span><select id="price-range"><option value="">Todos los precios</option>@foreach($lotSearchPriceRanges as $range)<option value="range-{{ $loop->iteration }}" @if($range['min_price'] !== null) data-min-price="{{ $range['min_price'] }}" @endif @if($range['max_price'] !== null) data-max-price="{{ $range['max_price'] }}" @endif>{{ $range['label'] }}</option>@endforeach</select></label>
    <button class="button" type="submit" aria-controls="resultados-lotes"><span data-search-label>Buscar lotes</span> ⌕</button>
</form><noscript><p>Activa JavaScript para consultar la disponibilidad de lotes.</p></noscript></div>
<section class="container indicators benefit-cards" aria-label="Nuestra propuesta">
    <article class="benefit-card benefit-card-stat" data-reveal="up">
        <span class="benefit-illustration illustration-projects" aria-hidden="true"><svg viewBox="0 0 82 72">
            <path class="ill-ground" d="m6 53 31-18 39 19-31 17Z"/><path class="ill-road" d="m26 62 31-18 7 3-31 18Z"/>
            <g class="ill-building ill-building-back"><path class="ill-side" d="m43 21 12 6v27l-12 7Z"/><path class="ill-front" d="m27 30 16-9v40l-16-8Z"/><path class="ill-top" d="m27 30 16-9 12 6-16 9Z"/><path class="ill-window" d="m32 34 5-3v6l-5 3Zm0 10 5-3v6l-5 3Z"/></g>
            <g class="ill-building ill-building-rise"><path class="ill-side" d="m61 36 9 4v19l-9 5Z"/><path class="ill-front" d="m49 43 12-7v28l-12-6Z"/><path class="ill-top" d="m49 43 12-7 9 4-12 7Z"/><path class="ill-window" d="m53 47 4-2v5l-4 2Z"/></g>
            <g class="ill-tree"><path d="M18 44v15"/><path class="ill-accent" d="M18 35c6 2 7 9 0 14-7-5-6-12 0-14Z"/></g>
        </svg></span><strong>03</strong><span class="benefit-description">Proyectos por descubrir</span>
    </article>
    <article class="benefit-card" data-reveal="up" style="--reveal-delay:90ms">
        <span class="benefit-illustration illustration-home" aria-hidden="true"><svg viewBox="0 0 82 72">
            <path class="ill-ground" d="m8 54 32-19 34 17-32 19Z"/><g class="ill-home"><path class="ill-side" d="m42 30 22 10v22L42 70Z"/><path class="ill-front" d="m19 43 23-13v40L19 59Z"/><path class="ill-roof" d="m14 42 27-17 28 13-6 6-22-10-22 13Z"/><path class="ill-door" d="m32 50 7-4v15l-7 4Z"/><path class="ill-window" d="m48 43 8 4v7l-8-4Z"/></g>
            <g class="ill-pin"><path class="ill-accent" d="M67 13c7 0 11 5 11 11 0 8-11 17-11 17S56 32 56 24c0-6 4-11 11-11Z"/><circle cx="67" cy="24" r="3.5"/></g>
        </svg></span><strong>Un lugar</strong><span class="benefit-description">Para tu próxima historia</span>
    </article>
    <article class="benefit-card" data-reveal="up" style="--reveal-delay:180ms">
        <span class="benefit-illustration illustration-finance" aria-hidden="true"><svg viewBox="0 0 82 72">
            <path class="ill-ground" d="m9 57 31-18 34 16-31 17Z"/><g class="ill-document"><path class="ill-paper" d="m21 16 37 8v39l-37-8Z"/><path class="ill-fold" d="m48 22 10 2v10l-10-2Z"/><path class="ill-line" d="m28 31 18 4m-18 7 23 5m-23 6 14 3"/></g>
            <g class="ill-coin ill-coin-one"><ellipse class="ill-accent" cx="61" cy="52" rx="11" ry="5"/><path class="ill-coin-side" d="M50 52v6c0 3 5 5 11 5s11-2 11-5v-6"/><path d="M61 48v8m-3-6 6 4"/></g>
            <g class="ill-coin ill-coin-two"><ellipse class="ill-accent-soft" cx="59" cy="44" rx="9" ry="4"/><path class="ill-coin-side" d="M50 44v5c0 2 4 4 9 4s9-2 9-4v-5"/></g>
        </svg></span><strong>A tu medida</strong><span class="benefit-description">Consulta opciones de pago</span>
    </article>
    <article class="benefit-card" data-reveal="up" style="--reveal-delay:270ms">
        <span class="benefit-illustration illustration-growth" aria-hidden="true"><svg viewBox="0 0 82 72">
            <path class="ill-ground" d="m7 59 29-17 39 13-30 17Z"/><g class="ill-bars"><path class="ill-bar ill-bar-one" d="m20 45 10-5v20l-10 6Z"/><path class="ill-bar ill-bar-two" d="m36 36 10-5v24l-10 6Z"/><path class="ill-bar ill-bar-three" d="m52 25 10-5v30l-10 6Z"/></g>
            <g class="ill-growth-arrow"><path d="m17 37 17-11 13 3 20-17"/><path class="ill-accent" d="m58 11 11-2-3 11Z"/></g><path class="ill-plot" d="m16 59 29-16 20 7-29 17Z"/>
        </svg></span><strong>Visión de futuro</strong><span class="benefit-description">Familias e inversionistas</span>
    </article>
</section>
<section class="lot-results" id="resultados-lotes" data-whatsapp="{{ $whatsapp }}" hidden aria-labelledby="lot-results-title">
    <div class="container">
        <div class="lot-results-heading"><div><span class="eyebrow">OPORTUNIDADES DISPONIBLES</span><h2 id="lot-results-title">Lotes disponibles para ti</h2><p id="lot-results-summary" role="status" aria-live="polite"></p></div><button class="lot-results-clear" id="reset-lot-search" type="button">Limpiar filtros</button></div>
        <div class="lot-results-feedback" id="lot-results-feedback" role="status" aria-live="polite"></div>
        <div class="lot-results-grid" id="lot-results-grid"></div>
    </div>
</section>
<section class="section container" id="proyectos">
    <div class="section-heading" data-reveal="up"><div><span class="eyebrow">EL INICIO DE ALGO GRANDE</span><h2>Explora nuestros proyectos</h2></div><p>Hay un lugar que va contigo.<br>Comienza a descubrirlo aquí.</p></div>
    <p class="catalog-note">Conoce nuestras propuestas. Consulta ubicaciones, precios y disponibilidad con un asesor.</p>
    <div class="project-grid">@forelse($homeProjects as $project)
        @php
            $profile = $project->webProfile;
            $location = collect([$project->district, $project->province, $project->department])->filter()->join(', ');
            $hasLots = (int) $project->status === 1 && $project->available_lots_count > 0;
            $projectWhatsapp = $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Hola, estoy interesado en conocer más sobre el proyecto '.$project->name.'. ¿Podrían brindarme información?') : $contactUrl;
            $availabilityMessage = match ($profile->commercial_status) {
                'coming_soon' => 'Un nuevo proyecto está por llegar.',
                'presale' => 'Sé de los primeros en conocer este proyecto.',
                'sold_out' => 'Actualmente no contamos con lotes disponibles.',
                default => 'Consulta la disponibilidad con un asesor.',
            };
        @endphp
        <article class="project-card" data-reveal="up" style="--reveal-delay:{{ $loop->index * 100 }}ms">
            <div class="project-image"><picture>@if($profile->mobile_image_url)<source media="(max-width: 760px)" srcset="{{ $profile->mobile_image_url }}">@endif<img src="{{ $profile->cover_image_url }}" alt="{{ $profile->image_alt ?: $project->name }}" loading="lazy"></picture><span class="project-tag">{{ $profile->badge_text ?: $profile->commercial_status_label }}</span></div>
            <div class="project-body"><span class="project-company">{{ $project->company?->business_name }}</span><span class="location">↗ {{ $location ?: 'Ubicación por confirmar' }}</span><h3>{{ $project->name }}</h3><p>{{ $profile->short_description ?: 'Conoce la propuesta y consulta sus condiciones comerciales.' }}</p>
                @if($hasLots)<div class="project-metrics"><span><strong>{{ $project->available_lots_count }}</strong> {{ $project->available_lots_count === 1 ? 'lote disponible' : 'lotes disponibles' }}</span>@if($project->available_lots_min_area)<span>Desde {{ number_format($project->available_lots_min_area, 0) }} m²</span>@endif</div>@else<div class="project-availability-note"><strong>{{ $profile->commercial_status_label }}</strong><span>{{ $availabilityMessage }}</span></div>@endif
                <div class="card-bottom">
                    <span>@if($hasLots && $project->available_lots_min_price) Desde S/ {{ number_format($project->available_lots_min_price, 0) }} @else Información comercial @endif</span>
                    @if($hasLots)<button type="button" class="text-button" data-project-lots="{{ $project->id }}" data-location="{{ $project->district }}">Ver lotes →</button>@else<a class="text-button" href="{{ $projectWhatsapp }}" target="_blank" rel="noopener noreferrer">{{ $profile->commercial_status === 'sold_out' ? 'Consultar nuevos proyectos' : 'Quiero información' }} →</a>@endif
                </div>
            </div>
        </article>
    @empty
        <div class="projects-empty"><h3>Muy pronto conocerás nuestros nuevos proyectos.</h3><p>Estamos preparando nuevas oportunidades para ti. Contáctanos para conocer las opciones disponibles.</p><a class="button" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">Hablar con un asesor →</a></div>
    @endforelse</div>
</section>
<section class="map-section" id="disponibilidad">
    <div class="container">
        @if($availabilityProjects->isNotEmpty())
        <div class="map-layout" id="project-availability" data-whatsapp="{{ $whatsapp }}">
            <div class="map-copy" data-reveal="left">
                <span class="eyebrow">MAPA Y DISPONIBILIDAD</span><h2>Tu próximo comienzo<br>tiene un lugar.</h2><p>Consulta el plano y la disponibilidad actual de cada proyecto.</p>
                <label class="field-label" for="map-project">Selecciona un proyecto</label>
                <select id="map-project">@foreach($availabilityProjects as $project)<option value="{{ $project->id }}" data-location="{{ $project->district }}" data-summary-url="{{ route('public.projects.availability', $project) }}" data-lots-url="{{ route('public.projects.available-lots', $project) }}" data-all-lots-url="{{ route('public.projects.lots', $project) }}">{{ $project->name }}</option>@endforeach</select>
                <div class="availability-dynamic" id="availability-dynamic" aria-live="polite">
                    <p class="availability-loading" id="availability-loading">Actualizando disponibilidad...</p>
                    <div id="availability-content" hidden>
                        <span class="availability-status" id="availability-status"></span>
                        <span class="availability-current-label" id="availability-current-label">DISPONIBILIDAD ACTUAL</span>
                        <div class="availability-counts" id="availability-counts"><span><i class="available"></i><strong data-count="available">0</strong> lotes disponibles</span></div>
                        <p class="availability-message" id="availability-message"></p>
                        <div class="availability-metrics" id="availability-metrics"><div data-metric="price"><small>DESDE</small><strong id="availability-price"></strong></div><div data-metric="area"><small>ÁREA DESDE</small><strong id="availability-area"></strong></div></div>
                        <button class="button" id="availability-lots-button" type="button">Ver lotes disponibles →</button><a class="button" id="availability-contact" href="#contacto" target="_blank" rel="noopener noreferrer">Quiero información →</a>
                    </div>
                </div>
            </div>
            <div class="map-frame project-plan" data-reveal="right" id="project-plan">
                <div class="map-top"><span id="map-title">Proyecto</span><span id="map-status">CARGANDO</span></div>
                <picture id="plan-picture" hidden><source id="plan-mobile-source" media="(max-width: 760px)"><img id="plan-image" src="" alt="" loading="lazy"></picture>
                <div class="plan-empty" id="plan-empty"><span aria-hidden="true">⌑</span><strong>PLANO PRÓXIMAMENTE</strong><p id="plan-empty-message">Estamos preparando la información de disponibilidad de este proyecto.</p></div>
                <div class="map-bottom"><span id="plan-caption"></span><button class="text-button" id="open-map" type="button" hidden>Ampliar plano ↗</button></div>
            </div>
        </div>
        <div class="project-lot-explorer" id="project-lot-explorer" hidden>
            <div class="project-lot-explorer-head">
                <div><span class="eyebrow">LOTES DISPONIBLES</span><h3 id="project-lot-explorer-title">Proyecto</h3><p id="project-lot-explorer-summary"></p></div>
                <button class="project-lot-explorer-close" id="project-lot-explorer-close" type="button">Ocultar lotes ↑</button>
            </div>
            <div class="project-lot-explorer-feedback" id="project-lot-explorer-feedback" role="status" aria-live="polite"></div>
            <div class="project-lot-explorer-grid" id="project-lot-explorer-grid"></div>
            <div class="project-lot-explorer-more"><a class="button button-outline" id="project-lot-explorer-all" href="#" hidden>Ver todos los lotes disponibles →</a></div>
        </div>
        @else
        <div class="map-empty-state"><span class="eyebrow">MAPA Y DISPONIBILIDAD</span><h2>Muy pronto podrás consultar nuestros proyectos y su disponibilidad.</h2></div>
        @endif
    </div>
</section>
<section class="container section invest-section" id="invertir" data-invest-section>
    <div class="invest-shell" data-reveal="up">
        <div class="invest-intro">
            <span class="eyebrow light">POR QUÉ INVERTIR CON GRUPO KREA</span>
            <h2>Una decisión importante<br>merece información clara.</h2>
            <p>Antes de elegir un lote, conoce el proyecto, revisa su disponibilidad y organiza tu presupuesto con referencias simples.</p>
            <div class="invest-manifesto" aria-hidden="true"><span>NO SE TRATA SOLO DE COMPRAR</span><strong>se trata de<br><em>decidir mejor.</em></strong></div>
        </div>
        <div class="invest-reasons" aria-label="Herramientas para evaluar tu inversión">
            <article class="invest-reason">
                <span class="invest-number">01</span>
                <div><h3>Ubicación y entorno</h3><p>Explora dónde se encuentra cada proyecto y conoce su contexto antes de decidir.</p><button class="invest-link" type="button" data-invest-open="location">Explorar ubicación <span aria-hidden="true">↗</span></button></div>
            </article>
            <article class="invest-reason">
                <span class="invest-number">02</span>
                <div><h3>Disponibilidad real</h3><p>Consulta lotes publicados, áreas y precios actuales directamente desde el inventario.</p><a class="invest-link" href="#disponibilidad" data-invest-scroll>Ver disponibilidad <span aria-hidden="true">↓</span></a></div>
            </article>
            <article class="invest-reason">
                <span class="invest-number">03</span>
                <div><h3>Opciones a tu medida</h3><p>Organiza un escenario referencial de presupuesto sin convertirlo en una cotización comercial.</p><button class="invest-link" type="button" data-invest-open="simulator">Organizar mi presupuesto <span aria-hidden="true">↗</span></button></div>
            </article>
            <article class="invest-reason">
                <span class="invest-number">04</span>
                <div><h3>Proceso transparente</h3><p>Conoce qué conviene revisar desde que eliges un proyecto hasta antes de realizar un pago.</p><button class="invest-link" type="button" data-invest-open="process">Conocer el proceso <span aria-hidden="true">↗</span></button></div>
            </article>
        </div>
    </div>
</section>
<section class="container about" id="nosotros" data-reveal="up"><span class="eyebrow">SOMOS GRUPO KREA</span><h2>Creemos en el valor<br>de un nuevo comienzo.</h2><p>Acompañamos a familias e inversionistas a explorar oportunidades de terrenos. Queremos que tomes tu próximo paso con información clara, atención cercana y una visión de futuro.</p><div class="about-values"><span>01 <strong>Atención cercana</strong></span><span>02 <strong>Decisiones informadas</strong></span><span>03 <strong>Visión a largo plazo</strong></span></div></section>
@include('portal.landing')
<section class="section container faq" id="preguntas"><div data-reveal="left"><span class="eyebrow">TE ACOMPAÑAMOS EN CADA PASO</span><h2>Preguntas frecuentes</h2><p>Tu tranquilidad comienza<br>con información clara.</p></div><div class="faq-list" data-reveal="up">
@foreach([
    '¿Qué documentos necesito para comprar un lote?' => 'La documentación depende del proyecto y de tu modalidad de compra. Solicita a un asesor la lista de requisitos antes de iniciar la separación.',
    '¿Cuáles son las formas de pago?' => 'Consulta los medios de pago autorizados y las condiciones del proyecto. Tu asesor puede explicarte el proceso antes de realizar cualquier abono.',
    '¿Los proyectos cuentan con título de propiedad?' => 'La situación registral debe verificarse para cada proyecto y lote. Solicita la documentación correspondiente y revisa las condiciones antes de comprar.',
    '¿Puedo financiar mi lote?' => 'Consulta las alternativas de financiamiento para el proyecto que te interesa. La inicial, las cuotas y los plazos están sujetos a sus condiciones comerciales.',
    '¿En cuánto tiempo se entrega el proyecto?' => 'Cada proyecto tiene su propio cronograma. Confirma la fecha y las condiciones de entrega en la información comercial y en tu contrato.',
    '¿Qué pasa si no puedo continuar con el pago?' => 'Comunícate con el equipo de atención para revisar tu caso. Las alternativas y posibles cargos dependen de las condiciones de tu contrato.'
] as $question => $answer)<details><summary>{{ $question }}<span aria-hidden="true">+</span></summary><p>{{ $answer }}</p></details>@endforeach
</div></section>
<section class="container journal" id="blog"><div class="section-heading"><div><span class="eyebrow">IDEAS PARA DECIDIR MEJOR</span><h2>Tu próxima inversión empieza aquí</h2></div><span class="journal-label">BLOG / GUÍA DE COMPRA</span></div><div class="guide-grid"><details><summary><span>01 / ELIGE CON INFORMACIÓN</span><h3>¿Qué revisar al elegir un terreno? ↗</h3></summary><p>Visita la ubicación, conoce los accesos y solicita información sobre servicios, documentación y condiciones de entrega del proyecto.</p></details><details><summary><span>02 / PLANIFICA TU FUTURO</span><h3>Prepara tu presupuesto de compra ↗</h3></summary><p>Considera la inicial, las cuotas y los gastos asociados. Pide una propuesta detallada para evaluar una alternativa acorde a tus posibilidades.</p></details><details><summary><span>03 / DA EL SIGUIENTE PASO</span><h3>Aprovecha tu visita al proyecto ↗</h3></summary><p>Prepara tus preguntas, recorre el entorno y solicita el plano del lote. Compara la información de tu visita con la propuesta comercial.</p></details></div></section>
<section class="container contact" id="contacto" data-reveal="up"><div><span class="eyebrow">CONVERSEMOS SOBRE TU FUTURO</span><h2>¿No encuentras tu respuesta?</h2><p>Hablemos por WhatsApp. Estamos para acompañarte.</p></div><a class="button icon-button" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer"><svg aria-hidden="true"><use href="#icon-whatsapp"></use></svg>Hablemos por WhatsApp ↗</a></section>
</main>
<footer class="footer">
    <div class="container footer-grid" data-reveal="up">
        <div><a class="brand" href="#inicio"><img src="{{ asset(config('landing.logo')) }}" width="46" height="46" alt=""><span>GRUPO <strong>KREA</strong><small>DESARROLLAMOS TU FUTURO</small></span></a><p>Un lugar para vivir.<br>Una oportunidad para crecer.</p></div>
        <div><h3>Explora</h3><a href="#proyectos">Nuestros proyectos</a><a href="#nosotros">Nosotros</a><a href="#invertir">Por qué invertir</a></div>
        <div><h3>Estamos contigo</h3><a href="#portal">Portal del cliente</a><a href="#preguntas">Preguntas frecuentes</a><a href="#blog">Guía de compra</a></div>
        <div class="footer-contact"><h3>Hablemos</h3>
            @include('landing.contact-footer')
        </div>
    </div>
    <div class="container footer-bottom"><span>© {{ date('Y') }} Grupo Krea. Todos los derechos reservados.</span><span>Hecho para crear futuro. <a href="#inicio">Volver arriba ↑</a></span></div>
</footer>
<dialog id="invest-location-dialog" class="invest-dialog" aria-labelledby="invest-location-title">
    <button class="dialog-close" type="button" aria-label="Cerrar">×</button>
    <span class="eyebrow">UBICACIÓN Y ENTORNO</span>
    <h2 id="invest-location-title">Conoce el proyecto antes de visitarlo.</h2>
    <p class="invest-dialog-lead">Selecciona un proyecto publicado para consultar su ubicación general y disponibilidad comercial.</p>
    @if($availabilityProjects->isNotEmpty())
        <label class="invest-field" for="invest-location-project"><span>PROYECTO</span><select id="invest-location-project">@foreach($availabilityProjects as $project)<option value="{{ $project->id }}" data-summary-url="{{ route('public.projects.availability', $project) }}">{{ $project->name }}</option>@endforeach</select></label>
        <div class="invest-location-summary" id="invest-location-summary" aria-live="polite">
            <div><small>EMPRESA</small><strong id="invest-location-company">Consultando...</strong></div>
            <div><small>UBICACIÓN</small><strong id="invest-location-place">—</strong></div>
            <div><small>ESTADO</small><strong id="invest-location-status">—</strong></div>
        </div>
        <p class="invest-dialog-note">La ubicación mostrada corresponde a los datos generales registrados para el proyecto. Confirma referencias y accesos con información oficial antes de una visita.</p>
        <button class="button invest-dialog-action" id="invest-location-availability" type="button">Ver disponibilidad del proyecto ↓</button>
    @else
        <div class="invest-empty">Muy pronto podrás explorar la ubicación de nuestros proyectos.</div>
    @endif
</dialog>

<dialog id="invest-simulator-dialog" class="invest-dialog invest-simulator-dialog" aria-labelledby="invest-simulator-title">
    <button class="dialog-close" type="button" aria-label="Cerrar">×</button>
    <span class="eyebrow">ORGANIZA TU PRESUPUESTO</span>
    <h2 id="invest-simulator-title">Haz números antes de tomar una decisión.</h2>
    <p class="invest-dialog-lead">Usa una referencia simple para visualizar cómo podrías distribuir un monto. No es una cotización ni una condición de financiamiento.</p>
    @if($availabilityProjects->isNotEmpty())
        <div class="invest-simulator-grid">
            <label class="invest-field invest-field-wide" for="invest-simulator-project"><span>PROYECTO</span><select id="invest-simulator-project">@foreach($availabilityProjects as $project)<option value="{{ $project->id }}" data-summary-url="{{ route('public.projects.availability', $project) }}" data-lots-url="{{ route('public.projects.lots', $project) }}">{{ $project->name }}</option>@endforeach</select></label>
            <label class="invest-field" for="invest-simulator-amount"><span>MONTO A EVALUAR (S/)</span><input id="invest-simulator-amount" type="number" min="0" step="100" inputmode="decimal" placeholder="25000"></label>
            <label class="invest-field" for="invest-simulator-initial"><span>INICIAL QUE CONSIDERAS (S/)</span><input id="invest-simulator-initial" type="number" min="0" step="100" inputmode="decimal" value="0"></label>
            <label class="invest-field invest-field-wide" for="invest-simulator-months"><span>HORIZONTE PARA ORGANIZARTE</span><select id="invest-simulator-months"><option value="12">12 meses</option><option value="24" selected>24 meses</option><option value="36">36 meses</option><option value="48">48 meses</option></select></label>
        </div>
        <div class="invest-simulator-result" aria-live="polite">
            <div><small>MONTO RESTANTE</small><strong id="invest-simulator-balance">S/ 0.00</strong></div>
            <div><small>REFERENCIA MENSUAL SIMPLE</small><strong id="invest-simulator-monthly">S/ 0.00</strong></div>
        </div>
        <p class="invest-simulator-source" id="invest-simulator-source">Consultando precio disponible del proyecto...</p>
        <p class="invest-dialog-note"><strong>Importante:</strong> la referencia mensual divide matemáticamente el monto restante entre los meses elegidos. No incluye intereses, gastos, promociones ni condiciones contractuales. Consulta las condiciones reales del proyecto antes de decidir.</p>
        <a class="button invest-dialog-action" id="invest-simulator-lots" href="#disponibilidad" hidden>Revisar lotes disponibles →</a>
    @else
        <div class="invest-empty">Muy pronto podrás organizar un escenario con nuestros proyectos publicados.</div>
    @endif
</dialog>

<dialog id="invest-process-dialog" class="invest-dialog invest-process-dialog" aria-labelledby="invest-process-title">
    <button class="dialog-close" type="button" aria-label="Cerrar">×</button>
    <span class="eyebrow">PROCESO TRANSPARENTE</span>
    <h2 id="invest-process-title">Cinco pasos para decidir con más información.</h2>
    <div class="invest-process-list">
        <div><span>01</span><p><strong>Explora el proyecto.</strong> Revisa ubicación, entorno y propuesta comercial.</p></div>
        <div><span>02</span><p><strong>Compara lotes.</strong> Consulta área, precio y disponibilidad actual.</p></div>
        <div><span>03</span><p><strong>Organiza tu presupuesto.</strong> Evalúa cuánto puedes destinar y solicita las condiciones comerciales vigentes.</p></div>
        <div><span>04</span><p><strong>Revisa la documentación.</strong> Solicita y verifica la información aplicable al proyecto y al lote elegido.</p></div>
        <div><span>05</span><p><strong>Confirma antes de pagar.</strong> Verifica contrato, importe, medio autorizado y condiciones antes de realizar un abono.</p></div>
    </div>
    <a class="button invest-dialog-action" href="#preguntas" data-dialog-scroll="preguntas">Ver preguntas frecuentes ↓</a>
</dialog>

<dialog id="project-dialog" aria-labelledby="project-dialog-title"><button class="dialog-close" type="button" aria-label="Cerrar detalle">×</button><span class="eyebrow">DESCUBRE TU PRÓXIMO COMIENZO</span><h2 id="project-dialog-title"></h2><p id="project-dialog-description"></p><p>Solicita la ubicación, precios, documentación y disponibilidad actualizados de este proyecto.</p><a class="button" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer" id="project-contact">Consultar proyecto ↗</a></dialog>
<dialog id="map-dialog" aria-labelledby="map-heading"><button class="dialog-close" type="button" aria-label="Cerrar plano">×</button><h2 id="map-heading">Plano · <span id="map-dialog-title"></span></h2><div class="map-dialog-scroll"><img id="map-dialog-image" src="" alt=""></div><p id="map-dialog-caption"></p></dialog>
</body>
</html>
