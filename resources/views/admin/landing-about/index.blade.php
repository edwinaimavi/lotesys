@extends('layouts.app')

@section('subtitle', 'Quiénes somos')

@section('header')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h1 class="mb-1 font-weight-bold text-dark">
                <i class="fas fa-users text-success mr-2"></i>Quiénes somos
            </h1>
            <small class="text-muted">Administra la identidad institucional que se muestra en la web pública.</small>
        </div>
        <span class="badge badge-{{ $about->is_active ? 'success' : 'secondary' }} px-3 py-2">
            {{ $about->is_active ? 'PUBLICADO' : 'OCULTO' }}
        </span>
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white shadow-sm rounded-pill px-3 py-2">
            <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-house-user mr-1"></i>Home</a></li>
            <li class="breadcrumb-item active">Web pública</li>
            <li class="breadcrumb-item active">Quiénes somos</li>
        </ol>
    </nav>
</div>
@stop

@section('content_body')
<div class="landing-about-admin">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <strong>Revisa la información ingresada.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.landing-about.update') }}" method="POST" enctype="multipart/form-data" id="landingAboutForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="is_active" value="0">

        <div class="row">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm about-admin-card">
                    <div class="card-header bg-white border-0">
                        <small class="about-admin-kicker">IDENTIDAD INSTITUCIONAL</small>
                        <h5 class="mb-0 font-weight-bold">Presentación de Grupo Krea</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="eyebrow">Texto superior</label>
                            <input id="eyebrow" name="eyebrow" maxlength="150" class="form-control"
                                   value="{{ old('eyebrow', $about->eyebrow) }}" placeholder="SOMOS GRUPO KREA">
                        </div>

                        <div class="form-group">
                            <label for="title">Título principal</label>
                            <input id="title" name="title" maxlength="220" required class="form-control"
                                   value="{{ old('title', $about->title) }}" placeholder="Quiénes somos">
                        </div>

                        <div class="form-group mb-0">
                            <label for="description">Quiénes somos</label>
                            <textarea id="description" name="description" rows="6" maxlength="3000" required
                                      class="form-control">{{ old('description', $about->description) }}</textarea>
                            <small class="form-text text-muted">Explica quién integra el grupo, su propósito y su aporte a la región.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm about-admin-card h-100">
                            <div class="card-header bg-white border-0">
                                <small class="about-admin-kicker">PROPÓSITO</small>
                                <h5 class="mb-0 font-weight-bold">Misión</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="mission_title">Título</label>
                                    <input id="mission_title" name="mission_title" maxlength="100" required class="form-control"
                                           value="{{ old('mission_title', $about->mission_title) }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label for="mission_text">Contenido</label>
                                    <textarea id="mission_text" name="mission_text" rows="7" maxlength="2500" required
                                              class="form-control">{{ old('mission_text', $about->mission_text) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mt-3 mt-lg-0">
                        <div class="card border-0 shadow-sm about-admin-card h-100">
                            <div class="card-header bg-white border-0">
                                <small class="about-admin-kicker">PROYECCIÓN</small>
                                <h5 class="mb-0 font-weight-bold">Visión</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="vision_title">Título</label>
                                    <input id="vision_title" name="vision_title" maxlength="100" required class="form-control"
                                           value="{{ old('vision_title', $about->vision_title) }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label for="vision_text">Contenido</label>
                                    <textarea id="vision_text" name="vision_text" rows="7" maxlength="2500" required
                                              class="form-control">{{ old('vision_text', $about->vision_text) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mt-3 mt-xl-0">
                <div class="card border-0 shadow-sm about-admin-card about-admin-side">
                    <div class="card-header bg-white border-0">
                        <small class="about-admin-kicker">IMAGEN INSTITUCIONAL</small>
                        <h5 class="mb-0 font-weight-bold">Presentación visual</h5>
                    </div>
                    <div class="card-body">
                        <div class="about-image-preview" id="aboutImagePreview">
                            @if($about->imageUrl())
                                <img src="{{ $about->imageUrl() }}" alt="{{ $about->image_alt ?: 'Imagen institucional' }}">
                            @else
                                <div class="about-image-placeholder">
                                    <i class="fas fa-building"></i>
                                    <strong>GRUPO KREA</strong>
                                    <span>Puedes agregar una imagen institucional.</span>
                                </div>
                            @endif
                        </div>

                        <div class="form-group mt-3">
                            <label for="image">Imagen <small class="text-muted">(opcional)</small></label>
                            <div class="custom-file">
                                <input id="image" name="image" type="file" class="custom-file-input" accept=".jpg,.jpeg,.png,.webp">
                                <label class="custom-file-label" for="image">Seleccionar imagen</label>
                            </div>
                            <small class="form-text text-muted">JPG, PNG o WEBP. Máximo 5 MB.</small>
                        </div>

                        <div class="form-group">
                            <label for="image_alt">Texto alternativo</label>
                            <input id="image_alt" name="image_alt" maxlength="180" class="form-control"
                                   value="{{ old('image_alt', $about->image_alt) }}" placeholder="Grupo Krea">
                        </div>

                        <div class="about-publish-box">
                            <label class="mb-0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $about->is_active))>
                                <span>Mostrar sección en la web pública</span>
                            </label>
                        </div>

                        @can('admin.landing-about.update')
                            <button class="btn btn-success btn-block mt-4 py-2" type="submit" id="saveLandingAbout">
                                <i class="fas fa-save mr-1"></i> Guardar cambios
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="about-admin-note">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Una sola configuración</strong>
                        <span>Este módulo administra la sección institucional completa de la página principal.</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@stop

@push('css')
<link rel="stylesheet" href="{{ asset('css/kreasys-landing-about.css') }}">
@endpush

@push('js')
<script src="{{ asset('js/kreasys-landing-about.js') }}"></script>
@endpush
