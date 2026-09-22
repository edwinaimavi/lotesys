@extends('layouts.app')

@section('subtitle', 'Slider principal')

@section('header')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h1 class="mb-1 font-weight-bold text-dark"><i class="fas fa-images text-success mr-2"></i>Slider principal</h1>
            <small class="text-muted">Contenido comercial del hero de la web pública.</small>
        </div>
        @can('admin.landing-sliders.store')
            <button class="btn btn-krea shadow-sm px-4" type="button" id="newLandingSlider"><i class="fas fa-plus-circle mr-1"></i> Nuevo slide</button>
        @endcan
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white shadow-sm rounded-pill px-3 py-2">
            <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-house-user mr-1"></i>Home</a></li>
            <li class="breadcrumb-item active">Web pública</li>
            <li class="breadcrumb-item active">Slider principal</li>
        </ol>
    </nav>
</div>
@stop

@section('content_body')
<div id="landingSliderModule" data-store-url="{{ route('admin.landing-sliders.store') }}" data-base-url="{{ url('admin/landing-sliders') }}" data-can-view="{{ auth()->user()->can('admin.landing-sliders.show') ? 1 : 0 }}" data-can-edit="{{ auth()->user()->can('admin.landing-sliders.update') ? 1 : 0 }}" data-can-toggle="{{ auth()->user()->can('admin.landing-sliders.toggle') ? 1 : 0 }}">
    <div id="activeSlidesWarning" class="alert alert-warning border-0 shadow-sm" @if($activeCount <= 5) hidden @endif><i class="fas fa-exclamation-triangle mr-2"></i>Actualmente tienes <span data-warning-active-count>{{ $activeCount }}</span> slides activos. Se recomienda mantener entre 3 y 5 para una mejor experiencia en la web.</div>

    <div class="card border-0 shadow-lg slider-card">
        <div class="card-header bg-white border-0 pt-4 pb-2">
            <h5 class="mb-1 font-weight-bold text-dark"><i class="fas fa-list text-success mr-2"></i>Slides registrados</h5>
            <small class="text-muted"><span data-slider-total>{{ $sliders->count() }}</span> registros · <span data-slider-active>{{ $activeCount }}</span> activos</small>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-hover text-center landing-slider-table mb-0">
                    <thead class="bg-light"><tr><th>#</th><th>IMAGEN</th><th class="text-left">TÍTULO</th><th>ORDEN</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
                    <tbody id="landingSliderTableBody">
                    @forelse($sliders as $slider)
                        <tr data-slider-id="{{ $slider->id }}" data-sort-order="{{ $slider->sort_order }}" data-active="{{ $slider->is_active ? 1 : 0 }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><img class="slider-thumb" src="{{ $slider->imageUrl() }}" alt="{{ $slider->image_alt ?: 'Miniatura del slide' }}"></td>
                            <td class="text-left"><strong class="d-block text-dark">{{ $slider->title }}</strong><small class="text-muted">{{ $slider->eyebrow ?: 'Sin eyebrow' }}</small></td>
                            <td><span class="order-pill">{{ $slider->sort_order }}</span></td>
                            <td><span class="badge badge-{{ $slider->is_active ? 'success' : 'secondary' }} px-3 py-2">{{ $slider->is_active ? 'ACTIVO' : 'INACTIVO' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del slide">
                                    @can('admin.landing-sliders.show')<button type="button" class="btn btn-outline-info view-slider" data-id="{{ $slider->id }}" title="Ver"><i class="fas fa-eye"></i></button>@endcan
                                    @can('admin.landing-sliders.update')<button type="button" class="btn btn-outline-primary edit-slider" data-id="{{ $slider->id }}" title="Editar"><i class="fas fa-pen"></i></button>@endcan
                                    @can('admin.landing-sliders.toggle')<button type="button" class="btn btn-outline-{{ $slider->is_active ? 'warning' : 'success' }} toggle-slider" data-id="{{ $slider->id }}" data-active="{{ $slider->is_active ? 1 : 0 }}" title="{{ $slider->is_active ? 'Desactivar' : 'Activar' }}"><i class="fas fa-{{ $slider->is_active ? 'pause' : 'play' }}"></i></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-state><td colspan="6" class="py-5 text-muted"><i class="far fa-images fa-2x d-block mb-2"></i>No hay slides registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.landing-sliders.partials.modal')
    @include('admin.landing-sliders.partials.viewModal')
</div>
@stop

@push('css')
<link rel="stylesheet" href="{{ asset('css/kreasys-landing-sliders.css') }}">
@endpush

@push('js')
<script src="{{ asset('js/kreasys-landing-sliders.js') }}"></script>
@endpush
