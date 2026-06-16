<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewLot" data-toggle="tooltip" title="Ver Lote"
        data-id="{{ $lot->id }}" data-project="{{ $lot->project->name ?? '—' }}"
        data-project_id="{{ $lot->project_id }}" data-block="{{ $lot->block->name ?? '—' }}"
        data-block_id="{{ $lot->block_id }}" data-code="{{ $lot->code }}" data-number="{{ $lot->number }}"
        data-area="{{ $lot->area }}" data-unit_measure="{{ $lot->unit_measure }}"
        data-cash_price="{{ $lot->cash_price }}" data-financed_price="{{ $lot->financed_price }}"
        data-north_boundary="{{ $lot->north_boundary }}" data-south_boundary="{{ $lot->south_boundary }}"
        data-east_boundary="{{ $lot->east_boundary }}" data-west_boundary="{{ $lot->west_boundary }}"
        data-status="{{ $lot->status }}" data-observation="{{ $lot->observation }}"
        data-created_at="{{ $lot->created_at ? $lot->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $lot->updated_at ? $lot->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $lot->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $lot->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editLot" data-toggle="tooltip" title="Editar Lote"
        data-id="{{ $lot->id }}" data-project_id="{{ $lot->project_id }}" data-block_id="{{ $lot->block_id }}"
        data-code="{{ $lot->code }}" data-number="{{ $lot->number }}" data-area="{{ $lot->area }}"
        data-unit_measure="{{ $lot->unit_measure }}" data-cash_price="{{ $lot->cash_price }}"
        data-financed_price="{{ $lot->financed_price }}" data-north_boundary="{{ $lot->north_boundary }}"
        data-south_boundary="{{ $lot->south_boundary }}" data-east_boundary="{{ $lot->east_boundary }}"
        data-west_boundary="{{ $lot->west_boundary }}" data-status="{{ $lot->status }}"
        data-observation="{{ $lot->observation }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteLot" data-id="{{ $lot->id }}"
        data-toggle="tooltip" title="Eliminar Lote">

        <i class="fas fa-trash"></i>

    </button>

</div>
