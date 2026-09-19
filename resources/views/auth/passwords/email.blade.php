@extends('layouts.auth-krea')

@section('title', 'Recuperar contraseña | KreaSys')

@section('auth_content')
    <div class="auth-heading">
        <span class="auth-eyebrow">Recuperación de acceso</span>
        <h2>Recuperar contraseña</h2>
        <p>Ingresa tu correo y te enviaremos las instrucciones para restablecer tu acceso.</p>
    </div>

    @if (session('status'))
        <div class="auth-alert auth-alert-success">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="email">Correo electrónico</label>
            <div class="auth-control @error('email') is-invalid @enderror">
                <i class="fas fa-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nombre@empresa.com" autocomplete="email" autofocus required>
            </div>
            @error('email')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>

        <button type="submit" class="auth-submit">
            <span>Enviar enlace de recuperación</span>
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>

    <div class="auth-secondary-action">
        <a href="{{ route('login') }}"><i class="fas fa-arrow-left mr-1"></i> Volver a iniciar sesión</a>
    </div>
@endsection
