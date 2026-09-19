<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'KreaSys'))</title>

    <link rel="icon" type="image/png" href="{{ asset('vendor/adminlte/dist/img/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kreasys-auth.css') }}?v=20260919">
</head>
<body class="krea-auth-page" style="--auth-bg: url('{{ asset('vendor/adminlte/dist/img/fondolote.jpg') }}');">
    <div class="auth-background" aria-hidden="true"></div>
    <div class="auth-background-overlay" aria-hidden="true"></div>
    <div class="auth-grid-pattern" aria-hidden="true"></div>

    <main class="auth-stage">
        <section class="auth-shell">
            <aside class="auth-showcase">
                <div class="showcase-glow showcase-glow-top"></div>
                <div class="showcase-glow showcase-glow-bottom"></div>

                <div class="showcase-brand">
                    <div class="showcase-logo-wrap">
                        <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="KreaSys" class="showcase-logo">
                    </div>
                    <div>
                        <div class="showcase-brand-name">KreaSys</div>
                        <div class="showcase-brand-subtitle">Gestión inmobiliaria</div>
                    </div>
                </div>

                <div class="showcase-content">
                    <span class="showcase-badge">
                        <span class="showcase-badge-dot"></span>
                        Plataforma inmobiliaria
                    </span>

                    <h1>Gestión clara para tus proyectos y lotes.</h1>
                    <p>
                        Centraliza proyectos, manzanas, lotes, clientes, ventas, cronogramas,
                        pagos y comprobantes en un entorno moderno y ordenado.
                    </p>

                    <div class="showcase-features">
                        <div class="showcase-feature">
                            <span class="feature-number">01</span>
                            <span>Proyectos y disponibilidad</span>
                        </div>
                        <div class="showcase-feature">
                            <span class="feature-number">02</span>
                            <span>Ventas y cronogramas</span>
                        </div>
                        <div class="showcase-feature">
                            <span class="feature-number">03</span>
                            <span>Pagos y comprobantes</span>
                        </div>
                    </div>
                </div>

                <div class="showcase-footer">
                    <span>© {{ date('Y') }} KreaSys</span>
                    <span class="secure-pill"><i class="fas fa-shield-alt"></i> Acceso seguro</span>
                </div>
            </aside>

            <section class="auth-panel">
                <div class="mobile-brand">
                    <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="KreaSys">
                    <div>
                        <strong>KreaSys</strong>
                        <span>Gestión inmobiliaria</span>
                    </div>
                </div>

                <div class="auth-panel-inner">
                    @yield('auth_content')
                </div>

                <footer class="auth-credit">
                    <span>Sistema de Gestión Inmobiliaria</span>
                    <span class="auth-credit-separator">•</span>
                    <span>by:</span>
                    <a href="https://cicosysperu.com/" target="_blank" rel="noopener noreferrer">CiCo Ingenieros</a>
                </footer>
            </section>
        </section>
    </main>

    @stack('scripts')
</body>
</html>
