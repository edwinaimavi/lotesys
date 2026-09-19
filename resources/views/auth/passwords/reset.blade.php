@extends('layouts.auth-krea')

@section('title', 'Nueva contraseña | KreaSys')

@section('auth_content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Seguridad de la cuenta</span>
        <h2>Crear nueva contraseña</h2>
        <p>Define una nueva contraseña segura para recuperar el acceso a tu cuenta.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="auth-form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth-field">
            <label for="email">Correo electrónico</label>
            <div class="auth-control @error('email') is-invalid @enderror">
                <i class="fas fa-envelope"></i>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" placeholder="nombre@empresa.com" autocomplete="email" required>
            </div>
            @error('email')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="auth-field">
            <label for="password">Nueva contraseña</label>
            <div class="auth-control @error('password') is-invalid @enderror">
                <i class="fas fa-lock"></i>
                <input id="password" type="password" name="password" placeholder="Nueva contraseña" autocomplete="new-password" required>
            </div>
            @error('password')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="auth-field">
            <label for="password-confirm">Confirmar nueva contraseña</label>
            <div class="auth-control">
                <i class="fas fa-lock"></i>
                <input id="password-confirm" type="password" name="password_confirmation" placeholder="Repite la contraseña" autocomplete="new-password" required>
            </div>
        </div>

        <button type="submit" class="auth-submit">
            <span>Actualizar contraseña</span>
            <i class="fas fa-check"></i>
        </button>
    </form>
@endsection
