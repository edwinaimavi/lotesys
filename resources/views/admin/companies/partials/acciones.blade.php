<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewCompany" data-bs-toggle="tooltip"
        data-bs-title="Ver Empresa" data-id="{{ $company->id }}" data-business_name="{{ $company->business_name }}"
        data-trade_name="{{ $company->trade_name }}" data-ruc="{{ $company->ruc }}"
        data-address="{{ $company->address }}" data-phone="{{ $company->phone }}" data-email="{{ $company->email }}"
        data-status="{{ $company->status }}"
        data-created_at="{{ $company->created_at ? $company->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $company->updated_at ? $company->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $company->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $company->updater->name ?? 'No registrado' }}">
        <i class="fas fa-eye"></i>
    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editCompany" data-bs-toggle="tooltip"
        data-bs-title="Editar Empresa" data-id="{{ $company->id }}"
        data-business_name="{{ $company->business_name }}" data-trade_name="{{ $company->trade_name }}"
        data-ruc="{{ $company->ruc }}" data-address="{{ $company->address }}" data-phone="{{ $company->phone }}"
        data-email="{{ $company->email }}" data-status="{{ $company->status }}">
        <i class="fas fa-pen"></i>
    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteCompany" data-id="{{ $company->id }}"
        data-bs-toggle="tooltip" data-bs-title="Eliminar Empresa">
        <i class="fas fa-trash"></i>
    </button>

</div>
