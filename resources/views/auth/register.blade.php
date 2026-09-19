@extends('layouts.auth-krea')

@section('title', 'Crear usuario | KreaSys')

@section('auth_content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Nuevo acceso</span>
        <h2>Crear usuario</h2>
        <p>Completa los datos para registrar una nueva cuenta de acceso.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Revisa los datos ingresados.</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="auth-form auth-form-compact" novalidate>
        @csrf

        <div class="auth-field">
            <label for="name">Nombre completo</label>
            <div class="auth-control @error('name') is-invalid @enderror">
                <i class="fas fa-user"></i>
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nombre y apellidos" autocomplete="name" autofocus required>
            </div>
            @error('name')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="auth-field">
            <label for="email">Correo electrónico</label>
            <div class="auth-control @error('email') is-invalid @enderror">
                <i class="fas fa-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nombre@empresa.com" autocomplete="email" required>
            </div>
            @error('email')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="auth-field-grid">
            <div class="auth-field">
                <label for="password">Contraseña</label>
                <div class="auth-control @error('password') is-invalid @enderror">
                    <i class="fas fa-lock"></i>
                    <input id="password" type="password" name="password" placeholder="Contraseña" autocomplete="new-password" required>
                </div>
                @error('password')<small class="auth-field-error">{{ $message }}</small>@enderror
            </div>

            <div class="auth-field">
                <label for="password-confirm">Confirmar contraseña</label>
                <div class="auth-control">
                    <i class="fas fa-lock"></i>
                    <input id="password-confirm" type="password" name="password_confirmation" placeholder="Repite la contraseña" autocomplete="new-password" required>
                </div>
            </div>
        </div>

        <button type="submit" class="auth-submit">
            <span>Crear cuenta</span>
            <i class="fas fa-user-plus"></i>
        </button>
    </form>

    <div class="auth-secondary-action">
        <span>¿Ya tienes una cuenta?</span>
        <a href="{{ route('login') }}">Iniciar sesión</a>
    </div>
@endsection
