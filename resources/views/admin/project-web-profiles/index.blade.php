@extends('layouts.app')

@section('subtitle', 'Proyectos web')

@section('header')
<div class="container-fluid">
    <h1 class="mb-1 font-weight-bold text-dark"><i class="fas fa-building text-success mr-2"></i>Proyectos web</h1>
    <small class="text-muted">Configura qué proyectos reales aparecen en la landing pública.</small>
</div>
@stop

@section('content_body')
<div id="projectWebModule"
     data-base-url="{{ url('admin/project-web-profiles') }}"
     data-can-view="{{ auth()->user()->can('admin.project-web-profiles.show') ? 1 : 0 }}"
     data-can-edit="{{ auth()->user()->can('admin.project-web-profiles.update') ? 1 : 0 }}">
    <div class="card border-0 shadow-lg web-project-card">
        <div class="card-header bg-white border-0 pt-4 pb-2">
            <h5 class="mb-1 font-weight-bold text-dark">Proyectos registrados</h5>
            <small class="text-muted">Todos los proyectos del sistema, incluidos los que aún no tienen configuración web.</small>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-hover web-project-table mb-0">
                    <thead class="bg-light"><tr><th>IMAGEN</th><th>PROYECTO</th><th>EMPRESA</th><th>UBICACIÓN</th><th>WEB</th><th>ESTADO COMERCIAL</th><th>DESTACADO</th><th>ORDEN</th><th>ACCIONES</th></tr></thead>
                    <tbody>
                    @forelse($projects as $project)
                        @php($profile = $project->webProfile)
                        <tr data-project-row="{{ $project->id }}">
                            <td data-cell="image">@if($profile?->cover_image_url)<img class="web-project-thumb" src="{{ $profile->cover_image_url }}" alt="">@else<span class="web-project-no-image"><i class="far fa-image"></i></span>@endif</td>
                            <td><strong>{{ $project->name }}</strong><small class="d-block text-muted">{{ $project->code }}</small></td>
                            <td>{{ $project->company?->business_name ?? 'Sin empresa' }}</td>
                            <td>{{ collect([$project->district, $project->province, $project->department])->filter()->join(', ') ?: 'Sin ubicación' }}</td>
                            <td data-cell="web"><span class="badge badge-{{ $profile?->show_on_web ? 'success' : 'secondary' }} px-3 py-2">{{ $profile?->show_on_web ? 'PUBLICADO' : 'NO PUBLICADO' }}</span></td>
                            <td data-cell="status">{{ $profile?->commercial_status_label ?? 'SIN CONFIGURAR' }}</td>
                            <td data-cell="featured">{{ $profile?->featured_on_home ? 'Sí' : 'No' }}</td>
                            <td data-cell="order">{{ $profile?->sort_order ?? '—' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @can('admin.project-web-profiles.show')<button type="button" class="btn btn-outline-info view-web-project" data-id="{{ $project->id }}" title="Ver"><i class="fas fa-eye"></i></button>@endcan
                                    @can('admin.project-web-profiles.update')<button type="button" class="btn btn-outline-primary edit-web-project" data-id="{{ $project->id }}" title="Configurar"><i class="fas fa-pen"></i></button>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-5 text-center text-muted">No hay proyectos registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.project-web-profiles.partials.modal')
    @include('admin.project-web-profiles.partials.viewModal')
</div>
@stop

@push('css')
<link rel="stylesheet" href="{{ asset('css/kreasys-project-web-profiles.css') }}">
@endpush
@push('js')
<script src="{{ asset('js/kreasys-project-web-profiles.js') }}"></script>
@endpush
