<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewBank" data-toggle="tooltip" title="Ver Banco"
        data-id="{{ $bank->id }}" data-bank_name="{{ $bank->bank_name }}" data-currency="{{ $bank->currency }}"
        data-account_number="{{ $bank->account_number }}" data-cci="{{ $bank->cci }}"
        data-account_holder="{{ $bank->account_holder }}" data-description="{{ $bank->description }}"
        data-status="{{ $bank->status }}"
        data-created_at="{{ $bank->created_at ? $bank->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $bank->updated_at ? $bank->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $bank->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $bank->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editBank" data-toggle="tooltip" title="Editar Banco"
        data-id="{{ $bank->id }}" data-bank_name="{{ $bank->bank_name }}" data-currency="{{ $bank->currency }}"
        data-account_number="{{ $bank->account_number }}" data-cci="{{ $bank->cci }}"
        data-account_holder="{{ $bank->account_holder }}" data-description="{{ $bank->description }}"
        data-status="{{ $bank->status }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteBank" data-id="{{ $bank->id }}"
        data-toggle="tooltip" title="Eliminar Banco">

        <i class="fas fa-trash"></i>

    </button>

</div>
