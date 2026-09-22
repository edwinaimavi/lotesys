<section class="portal-section" id="portal"><div class="container portal-layout">
    <div class="portal-access-copy" data-reveal="left">
        <span class="eyebrow">SIEMPRE CERCA DE TI</span>
        <h2>Tu inversión,<br><span>más cerca que nunca.</span></h2>
        <p class="portal-access-intro">Un espacio privado para consultar tu operación y acompañar cada paso de tu inversión.</p>
        <ul class="portal-access-benefits">
            @foreach(['Consulta tus cuotas','Revisa tus pagos','Conoce tu saldo pendiente','Descarga tus comprobantes'] as $benefit)
                <li><span class="portal-access-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"/></svg></span><span>{{ $benefit }}</span></li>
            @endforeach
        </ul>
        <p class="portal-access-trust">Acceso rápido a la información principal de tu operación.</p>
    </div>
    <div class="login-card portal-access-card" data-reveal="right">
        <div class="portal-access-card-top"><span class="portal-access-badge">Acceso privado</span><svg class="portal-access-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg></div>
        <h3>Portal del cliente</h3><p>Tu espacio para seguir avanzando.</p>
        <form id="customer-portal-login" action="{{ route('portal.login') }}" method="POST" data-base="{{ url('/portal-cliente') }}">
            @csrf<label for="portal-dni">DNI</label>
            <div class="portal-access-field"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 16c0-4 6-4 6 0m3-6h4m-4 4h4"/></svg><input id="portal-dni" name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" autocomplete="username" placeholder="Tu DNI de 8 dígitos" required></div>
            <label for="portal-password">Contraseña</label>
            <div class="portal-access-field portal-access-password"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="portal-password" name="password" type="password" autocomplete="current-password" maxlength="72" placeholder="Ingresa tu contraseña" required><button id="portal-password-toggle" type="button" aria-label="Mostrar contraseña" aria-controls="portal-password" aria-pressed="false" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="portal-eye-slash" d="m3 3 18 18"/></svg></button></div>
            <p id="portal-login-message" role="status" aria-live="polite"></p><button class="button full-width portal-access-submit" type="submit"><span>Ingresar a mi portal</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></button>
        </form><button id="portal-reopen" class="button full-width" type="button" hidden>Abrir mi portal</button>
    </div>
</div></section>
<dialog id="customer-portal" class="customer-portal" aria-modal="true" aria-labelledby="cp-title">
    <header class="cp-header"><div><span class="eyebrow">GRUPO KREA · PORTAL DEL CLIENTE</span><h2 id="cp-title">Bienvenido a tu portal</h2><p>Tu inversión, organizada en un solo lugar.</p></div><div class="cp-actions"><button type="button" id="cp-logout">Cerrar sesión</button><button type="button" id="cp-close" aria-label="Cerrar portal">×</button></div></header>
    <div class="cp-body"><p id="cp-message" role="status" aria-live="polite"></p>
        <form id="cp-password" hidden><h3>Protege tu cuenta</h3><p>Crea una contraseña de al menos 8 caracteres con letras y números, diferente de tu DNI.</p><label for="cp-new-password">Nueva contraseña</label><input id="cp-new-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required><label for="cp-confirm-password">Confirmar contraseña</label><input id="cp-confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required><button type="submit" class="button">Guardar y continuar</button></form>
        <div id="cp-content" hidden><div class="cp-operation"><div><label for="cp-operation">Mis operaciones</label><p id="cp-operation-help">Elige una operación para consultar sus detalles.</p></div><select id="cp-operation" aria-describedby="cp-operation-help"></select></div>
            <div class="cp-tabs" role="tablist" aria-label="Información de tu operación">@foreach(['summary'=>'Resumen','schedules'=>'Cronograma','payments'=>'Pagos','documents'=>'Comprobantes'] as $key=>$label)<button type="button" id="cp-tab-{{ $key }}" role="tab" data-panel="{{ $key }}" aria-controls="cp-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">{{ $label }}</button>@endforeach</div>
            @foreach(['summary','schedules','payments','documents'] as $key)<section id="cp-{{ $key }}" role="tabpanel" aria-labelledby="cp-tab-{{ $key }}" tabindex="0" @if(!$loop->first) hidden @endif></section>@endforeach
            <a id="cp-pdf" class="button" hidden><svg class="cp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 16v5h14v-5"/></svg><span>Descargar estado de cuenta</span><small>PDF</small></a>
        </div>
    </div>
</dialog>
