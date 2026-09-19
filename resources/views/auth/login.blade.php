@extends('layouts.auth-krea')

@section('title', 'Iniciar sesión | KreaSys')

@section('auth_content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Bienvenido</span>
        <h2>Iniciar sesión</h2>
        <p>Ingresa tus credenciales para acceder al panel administrativo.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>No pudimos iniciar sesión.</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="auth-alert auth-alert-success">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="email">Correo electrónico</label>
            <div class="auth-control @error('email') is-invalid @enderror">
                <i class="fas fa-envelope"></i>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="nombre@empresa.com"
                    autocomplete="email"
                    autofocus
                    required
                >
            </div>
            @error('email')
                <small class="auth-field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password">Contraseña</label>
            <div class="auth-control @error('password') is-invalid @enderror">
                <i class="fas fa-lock"></i>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña">
                    <i class="far fa-eye"></i>
                </button>
            </div>
            @error('password')
                <small class="auth-field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="auth-options">
            <label class="auth-check" for="remember">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <span class="check-box"><i class="fas fa-check"></i></span>
                <span>Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit" class="auth-submit">
            <span>Ingresar al sistema</span>
            <i class="fas fa-arrow-right"></i>
        </button>
    </form>

    @if (Route::has('register'))
        <div class="auth-secondary-action">
            <span>¿No tienes una cuenta?</span>
            <a href="{{ route('register') }}">Crear usuario</a>
        </div>
    @endif

    <div class="auth-security-note">
        <div class="auth-security-icon"><i class="fas fa-shield-alt"></i></div>
        <div>
            <strong>Acceso restringido</strong>
            <p>Solo para usuarios autorizados. La actividad puede ser registrada para control interno.</p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.target);
            const icon = button.querySelector('i');
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            icon.classList.toggle('fa-eye', showing);
            icon.classList.toggle('fa-eye-slash', !showing);
            button.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    });
</script>
@endpush
