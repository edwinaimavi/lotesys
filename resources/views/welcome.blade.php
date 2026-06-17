<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>CiCoSys | Bienvenida</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <style>
        :root {
            --bg-1: #0f172a;
            --bg-2: #111827;
            --card: rgba(255, 255, 255, .08);
            --card-border: rgba(255, 255, 255, .12);
            --text: #e5eefc;
            --muted: rgba(229, 238, 252, .72);
            --accent: #3b82f6;
            --accent-2: #22c55e;
            --accent-3: #f59e0b;
            --danger: #ef4444;
            --shadow: 0 24px 80px rgba(0, 0, 0, .35);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, .20), transparent 25%),
                radial-gradient(circle at top right, rgba(34, 197, 94, .12), transparent 22%),
                linear-gradient(160deg, var(--bg-1), var(--bg-2));
            color: var(--text);
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 28px;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: .2px;
            color: #fff;
            text-decoration: none;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(145deg, #2563eb, #60a5fa);
            display: grid;
            place-items: center;
            box-shadow: 0 12px 30px rgba(37, 99, 235, .35);
            font-size: 20px;
        }

        .auth-links {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .auth-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 12px;
            transition: .2s ease;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .03);
        }

        .auth-links a:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, .08);
        }

        .auth-links .primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-color: transparent;
        }

        .hero-wrap {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .hero {
            width: min(1240px, 100%);
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 24px;
            align-items: stretch;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow);
            border-radius: 28px;
            backdrop-filter: blur(18px);
            overflow: hidden;
        }

        .left {
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .title {
            font-size: clamp(2.2rem, 5vw, 4.2rem);
            line-height: 1.02;
            margin: 0;
            color: #fff;
            letter-spacing: -1px;
        }

        .subtitle {
            margin-top: 18px;
            max-width: 680px;
            font-size: 1.02rem;
            line-height: 1.75;
            color: var(--muted);
        }

        .rotator {
            margin-top: 22px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
            color: #fff;
            width: fit-content;
        }

        .rotator .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 8px rgba(34, 197, 94, .12);
            flex: none;
        }

        .rotator .message {
            font-weight: 600;
            transition: opacity .25s ease, transform .25s ease;
        }

        .clock-card {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            max-width: 560px;
        }

        .info-box {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: rgba(229, 238, 252, .64);
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            transition: .2s ease;
            border: 1px solid transparent;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, .06);
            border-color: rgba(255, 255, 255, .12);
            color: white;
        }

        .right {
            padding: 26px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .visual {
            flex: 1;
            border-radius: 24px;
            background:
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, .30), transparent 28%),
                radial-gradient(circle at 80% 15%, rgba(34, 197, 94, .22), transparent 22%),
                radial-gradient(circle at 50% 90%, rgba(245, 158, 11, .16), transparent 26%),
                linear-gradient(160deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .03));
            border: 1px solid rgba(255, 255, 255, .10);
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 320px;
        }

        .visual::before,
        .visual::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            opacity: .55;
        }

        .visual::before {
            width: 180px;
            height: 180px;
            border: 1px solid rgba(255, 255, 255, .18);
            top: -40px;
            right: -50px;
            transform: rotate(20deg);
        }

        .visual::after {
            width: 260px;
            height: 260px;
            border: 1px dashed rgba(255, 255, 255, .10);
            bottom: -100px;
            left: -70px;
        }

        .badge-live {
            width: fit-content;
            margin: 0 auto 18px auto;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(34, 197, 94, .14);
            color: #a7f3d0;
            border: 1px solid rgba(34, 197, 94, .25);
            font-size: 12px;
            font-weight: 700;
        }

        .big-clock {
            font-size: clamp(2.6rem, 6vw, 4.8rem);
            font-weight: 900;
            letter-spacing: -2px;
            color: #fff;
            line-height: 1;
            margin: 0;
        }

        .big-date {
            margin-top: 10px;
            font-size: 1rem;
            color: rgba(229, 238, 252, .8);
        }

        .quote {
            margin-top: 22px;
            font-size: 1.05rem;
            line-height: 1.8;
            color: rgba(229, 238, 252, .92);
        }

        .mini-grid {
            margin-top: 22px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .mini-card {
            padding: 14px 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
        }

        .mini-card .num {
            display: block;
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
        }

        .mini-card .txt {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: rgba(229, 238, 252, .72);
        }

        .news-panel {
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
        }

        .news-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .news-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: #fff;
            font-size: 1rem;
        }

        .news-pill {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(59, 130, 246, .18);
            border: 1px solid rgba(59, 130, 246, .25);
            color: #bfdbfe;
            font-size: 12px;
            font-weight: 700;
        }

        .news-list {
            display: grid;
            gap: 10px;
        }

        .news-item {
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(15, 23, 42, .35);
            border: 1px solid rgba(255, 255, 255, .08);
        }

        .news-item .tag {
            font-size: 12px;
            font-weight: 800;
            color: #93c5fd;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .news-item .headline {
            margin-top: 6px;
            font-size: 14px;
            line-height: 1.5;
            color: #fff;
            font-weight: 600;
        }

        .news-item .meta {
            margin-top: 6px;
            font-size: 12px;
            color: rgba(229, 238, 252, .65);
        }

        .footer-note {
            padding: 18px 28px 24px;
            text-align: center;
            color: rgba(229, 238, 252, .55);
            font-size: 13px;
        }

        @media (max-width: 980px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .left,
            .right {
                padding: 26px;
            }

            .clock-card {
                grid-template-columns: 1fr;
            }

            .mini-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .topbar {
                padding: 18px 16px;
                flex-direction: column;
                align-items: flex-start;
            }

            .auth-links {
                width: 100%;
            }

            .hero-wrap {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="topbar">
            <a href="{{ url('/') }}" class="brand">
                <div class="brand-mark">C</div>
                <div>
                    <div style="font-size: 1rem;">CiCoSys</div>
                    <div style="font-size: .82rem; color: rgba(229,238,252,.7); font-weight: 600;">Gestión inmobiliaria
                        y financiera</div>
                </div>
            </a>

            @if (Route::has('login'))
                <div class="auth-links">
                    @auth
                        <a href="{{ url('/home') }}" class="primary">Entrar al sistema</a>
                    @else
                        <a href="{{ route('login') }}">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="primary">Crear cuenta</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>

        <div class="hero-wrap">
            <div class="hero">
                <section class="panel left">
                    <div class="eyebrow">
                        <span>●</span>
                        Bienvenido
                    </div>

                    <h1 class="title">
                        Un espacio para
                        <span style="color:#60a5fa;">ordenar</span>,
                        <span style="color:#22c55e;">controlar</span> y
                        <span style="color:#f59e0b;">crecer</span>.
                    </h1>

                    <p class="subtitle">
                        Aquí comenzará tu plataforma de gestión. Por ahora tendrás una bienvenida limpia, moderna y
                        clara,
                        con información útil al instante mientras sigues construyendo el sistema.
                    </p>

                    <div class="rotator">
                        <span class="dot"></span>
                        <span class="message" id="rotatingMessage">Todo listo para empezar un gran día.</span>
                    </div>

                    <div class="clock-card">
                        <div class="info-box">
                            <div class="info-label">Hora actual</div>
                            <div class="info-value" id="clock">--:--:--</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Fecha de hoy</div>
                            <div class="info-value" id="date">-- / -- / ----</div>
                        </div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <span>Ingresar</span>
                            <span>→</span>
                        </a>
                        <a href="#news" class="btn btn-secondary">
                            Ver noticias
                        </a>
                    </div>
                </section>

                <section class="panel right">
                    <div class="visual">
                        <div class="badge-live">Sistema en construcción</div>
                        <div class="big-clock" id="bigClock">--:--</div>
                        <div class="big-date" id="bigDate">--</div>

                        <div class="quote" id="quoteText">
                            “La organización de hoy es la tranquilidad de mañana.”
                        </div>

                        <div class="mini-grid">
                            <div class="mini-card">
                                <span class="num">01</span>
                                <span class="txt">Ventas</span>
                            </div>
                            <div class="mini-card">
                                <span class="num">02</span>
                                <span class="txt">Pagos</span>
                            </div>
                            <div class="mini-card">
                                <span class="num">03</span>
                                <span class="txt">Lotes</span>
                            </div>
                        </div>
                    </div>

                    <div class="news-panel" id="news">
                        <div class="news-head">
                            <div class="news-title">
                                <i class="fas fa-newspaper"></i>
                                Noticias destacadas
                            </div>
                            <div class="news-pill">Perú / Mundo</div>
                        </div>

                        <div class="news-list" id="newsList">
                            <div class="news-item">
                                <div class="tag">Perú</div>
                                <div class="headline">Seguimiento político y económico del país con foco en coyuntura
                                    nacional.</div>
                                <div class="meta">Actualización automática de ejemplo</div>
                            </div>

                            <div class="news-item">
                                <div class="tag">Mundo</div>
                                <div class="headline">Mercados, tecnología y decisiones globales que mueven la agenda
                                    internacional.</div>
                                <div class="meta">Actualización automática de ejemplo</div>
                            </div>

                            <div class="news-item">
                                <div class="tag">Economía</div>
                                <div class="headline">Señales de inflación, tasas y comportamiento de inversión para
                                    este ciclo.</div>
                                <div class="meta">Actualización automática de ejemplo</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="footer-note">
            CiCoSys • portada de bienvenida • luego podrás convertir esta pantalla en una landing con noticias reales
            conectadas al servidor.
        </div>
    </div>

    <script>
        const messages = [
            "Todo listo para empezar un gran día.",
            "Hoy también avanzas un paso más.",
            "Organiza tus ventas, pagos y lotes desde un solo lugar.",
            "Menos caos, más control.",
            "Tu sistema está tomando forma."
        ];

        const quotes = [
            "“La organización de hoy es la tranquilidad de mañana.”",
            "“Lo simple, cuando está bien hecho, se vuelve poderoso.”",
            "“Cada registro ordenado es una decisión más clara.”",
            "“La constancia construye sistemas que sí funcionan.”"
        ];

        let msgIndex = 0;
        let quoteIndex = 0;

        function updateClock() {
            const now = new Date();

            const time = now.toLocaleTimeString('es-PE', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            const fullDate = now.toLocaleDateString('es-PE', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const shortDate = now.toLocaleDateString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            document.getElementById('clock').textContent = time;
            document.getElementById('bigClock').textContent = now.toLocaleTimeString('es-PE', {
                hour: '2-digit',
                minute: '2-digit'
            });

            document.getElementById('date').textContent = shortDate;
            document.getElementById('bigDate').textContent = fullDate.charAt(0).toUpperCase() + fullDate.slice(1);
        }

        function rotateContent() {
            const messageEl = document.getElementById('rotatingMessage');
            const quoteEl = document.getElementById('quoteText');

            messageEl.style.opacity = 0;
            messageEl.style.transform = 'translateY(4px)';

            setTimeout(() => {
                msgIndex = (msgIndex + 1) % messages.length;
                quoteIndex = (quoteIndex + 1) % quotes.length;

                messageEl.textContent = messages[msgIndex];
                quoteEl.textContent = quotes[quoteIndex];

                messageEl.style.opacity = 1;
                messageEl.style.transform = 'translateY(0)';
            }, 180);
        }

        updateClock();
        setInterval(updateClock, 1000);
        setInterval(rotateContent, 3500);
    </script>
</body>

</html>
