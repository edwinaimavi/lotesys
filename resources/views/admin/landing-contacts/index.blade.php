@extends('layouts.app')
@section('subtitle', 'Contacto y redes')
@section('header')
<div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <div><small class="text-success font-weight-bold">WEB PÚBLICA</small><h1 class="font-weight-bold">Contacto y redes</h1><p class="text-muted">Administra cómo tus clientes se comunican con Grupo Krea.</p></div>
    @can('admin.landing-contacts.store')<button class="btn btn-success px-4" id="contact-add" type="button">+ Agregar contacto</button>@endcan
</div>
@stop
@section('content_body')
<div id="contact-module" data-base="{{ url('admin/landing-contacts') }}" data-show="{{ auth()->user()->can('admin.landing-contacts.show') ? 1 : 0 }}" data-update="{{ auth()->user()->can('admin.landing-contacts.update') ? 1 : 0 }}" data-toggle="{{ auth()->user()->can('admin.landing-contacts.toggle') ? 1 : 0 }}">
    <div class="lc-stats"><div><small>CONTACTOS ACTIVOS</small><strong id="lc-active">{{ $items->where('is_active', true)->count() }}</strong></div><div><small>WHATSAPP CTA</small><strong id="lc-cta">{{ $items->where('is_active', true)->where('use_for_cta', true)->first()?->value ?: 'Configuración de respaldo' }}</strong></div><div><small>REDES ACTIVAS</small><strong id="lc-social">{{ $items->where('is_active', true)->where('type', 'social')->count() }}</strong></div></div>
    <p class="text-muted small">Sin contactos activos, la web utiliza los datos de respaldo. El WhatsApp CTA puede mantenerse oculto en el footer.</p>
    <div class="card lc-card"><div class="card-body table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Orden</th><th>Tipo</th><th>Etiqueta</th><th>Valor</th><th>Uso</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="contact-rows"></tbody></table></div></div>
    <script id="contact-initial" type="application/json">@json($items)</script>
    <div class="modal fade" id="contact-modal" tabindex="-1" aria-labelledby="contact-modal-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><small>WEB PÚBLICA</small><h5 class="modal-title" id="contact-modal-title">Agregar contacto</h5></div><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">×</span></button></div>
        <form id="contact-form">@csrf
            <div class="modal-body">
                <p id="contact-error" class="alert alert-danger" role="alert" hidden></p>
                <div class="row">
                    <div class="form-group col-md-6"><label for="lc-type">Tipo</label><select id="lc-type" name="type" class="form-control"><option value="whatsapp">WhatsApp</option><option value="phone">Teléfono</option><option value="email">Email</option><option value="address">Dirección</option><option value="social">Red social</option></select><small class="invalid-feedback" data-error="type"></small></div>
                    <div class="form-group col-md-6"><label for="lc-label">Etiqueta <small>(opcional)</small></label><input id="lc-label" name="label" maxlength="255" class="form-control"><small class="invalid-feedback" data-error="label"></small></div>
                    <div class="form-group col-12" data-field="value"><label for="lc-value" id="lc-value-label">Número</label><textarea id="lc-value" name="value" class="form-control" maxlength="1000" rows="2" aria-describedby="lc-value-help"></textarea><small id="lc-value-help" class="form-text text-muted">Incluye código de país. Ejemplo: +51 972 873 511.</small><small class="invalid-feedback" data-error="value"></small></div>
                    <div class="form-group col-md-6" data-field="network" hidden><label for="lc-network">Red social</label><select id="lc-network" name="network" class="form-control">@foreach(\App\Models\LandingContactItem::NETWORKS as $network)<option value="{{ $network }}">{{ ucfirst($network) }}</option>@endforeach</select><small class="invalid-feedback" data-error="network"></small></div>
                    <div class="form-group col-12" data-field="url" hidden><label for="lc-url" id="lc-url-label">URL</label><input id="lc-url" name="url" type="url" maxlength="2048" placeholder="https://" class="form-control"><small class="invalid-feedback" data-error="url"></small></div>
                    <div class="form-group col-md-6"><label for="lc-order">Orden</label><input id="lc-order" name="sort_order" type="number" min="1" max="4294967295" value="1" required class="form-control"><small class="invalid-feedback" data-error="sort_order"></small></div>
                </div>
                <div class="lc-options">
                    @foreach(['is_primary'=>'Contacto principal de su categoría','use_for_cta'=>'Usar como WhatsApp CTA principal','show_in_footer'=>'Mostrar en footer','is_active'=>'Activo'] as $key=>$label)
                        <div data-field="{{ $key }}"><label><input type="checkbox" name="{{ $key }}" @if(in_array($key,['show_in_footer','is_active'])) checked @endif> {{ $label }}</label><small class="text-danger d-block" data-error="{{ $key }}"></small></div>
                    @endforeach
                </div>
                <p class="small text-muted mt-3">Al elegir un WhatsApp CTA, se reemplaza automáticamente el anterior. Solo los contactos activos se publican.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cerrar</button><button class="btn btn-success px-4" type="submit" id="contact-save">Guardar contacto</button></div>
        </form>
    </div></div></div>
</div>
@stop
@push('css')<link rel="stylesheet" href="{{ asset('css/kreasys-landing-contacts.css') }}">@endpush
@push('js')<script src="{{ asset('js/kreasys-landing-contacts.js') }}"></script>@endpush
