<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlockController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $projects = Project::with('company')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('admin.blocks.index', compact('projects'));
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $blocks = Block::with(
            'project.company',
            'creator',
            'updater',
            'lots'
        )
            ->where('status', '!=', -1)
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($blocks)

            ->addIndexColumn()

            ->addColumn('company', function ($block) {

                $company = $block->project?->company;

                return $company?->business_name
                    ?? $company?->trade_name
                    ?? '—';
            })

            ->addColumn('project', function ($block) {

                return $block->project->name ?? '—';
            })

            ->editColumn('description', function ($block) {

                return $block->description
                    ? $block->description
                    : '—';
            })

            ->editColumn('status', function ($block) {

                return $block->status == 1

                    ? '<span class="badge bg-success text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> Activo
                       </span>'

                    : '<span class="badge bg-danger text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-times-circle me-1"></i> Inactivo
                       </span>';
            })

            ->addColumn('acciones', function ($block) {

                return view('admin.blocks.partials.acciones', compact('block'))->render();
            })

            ->rawColumns(['status', 'acciones'])

            ->make(true);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'project_id' => [
                'required',
                'exists:projects,id'
            ],

            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'project_id.required' => 'El proyecto es obligatorio.',

            'project_id.exists' => 'El proyecto seleccionado no existe.',

            'name.required' => 'El nombre de la manzana es obligatorio.'

        ]);

        try {

            DB::beginTransaction();

            // =====================================================
            // NORMALIZAR NOMBRE DE MANZANA
            // =====================================================

            $name = strtoupper(trim($data['name']));

            // quitar espacios
            $name = str_replace(' ', '', $name);

            // quitar MZ si ya existe
            $name = preg_replace('/^MZ/', '', $name);

            // resultado final
            $data['name'] = 'MZ ' . $name;

            // =====================================================
            // VALIDAR DUPLICIDAD
            // =====================================================

            $exists = Block::where('project_id', $data['project_id'])
                ->where('name', $data['name'])
                ->where('status', '!=', -1)
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'name' => [
                            'La manzana ya existe en este proyecto.'
                        ]
                    ]

                ], 422);
            }

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            $block = Block::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Manzana registrada correctamente.',

                'data' => $block

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error creating block: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar la manzana.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $block = Block::find($id);

        if (! $block) {

            return response()->json([

                'status' => 'error',

                'message' => 'Manzana no encontrada.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $block

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $block = Block::find($id);

        if (! $block) {

            return response()->json([

                'status' => 'error',

                'message' => 'Manzana no encontrada.'

            ], 404);
        }

        $data = $request->validate([

            'project_id' => [
                'required',
                'exists:projects,id'
            ],

            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'project_id.required' => 'El proyecto es obligatorio.',

            'project_id.exists' => 'El proyecto seleccionado no existe.',

            'name.required' => 'El nombre de la manzana es obligatorio.'

        ]);

        try {

            DB::beginTransaction();
            // =====================================================
            // NORMALIZAR NOMBRE DE MANZANA
            // =====================================================

            $name = strtoupper(trim($data['name']));

            // quitar espacios
            $name = str_replace(' ', '', $name);

            // quitar MZ si ya existe
            $name = preg_replace('/^MZ/', '', $name);

            // resultado final
            $data['name'] = 'MZ ' . $name;

            // =====================================================
            // VALIDAR DUPLICIDAD
            // =====================================================

            $exists = Block::where('project_id', $data['project_id'])
                ->where('name', $data['name'])
                ->where('id', '!=', $block->id)
                ->where('status', '!=', -1)
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'name' => [
                            'La manzana ya existe en este proyecto.'
                        ]
                    ]

                ], 422);
            }

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            $block->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Manzana actualizada correctamente.',

                'data' => $block->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error updating block: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar la manzana.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(Block $block)
    {
        $block->update([

            'status' => -1

        ]);

        return response()->json([

            'message' => 'Manzana eliminada correctamente.'

        ]);
    }

    /**
     * GENERAR LOTES MASIVOS
     */
    /**
     * GENERAR LOTES MASIVOS
     */
    /**
     * GENERAR LOTES MASIVOS
     */
    public function generateLots(Request $request)
    {
        $request->validate([

            'project_id' => 'required|exists:projects,id',

            'block_id' => 'required|exists:blocks,id',

            'quantity' => 'required|integer|min:1',

            'area' => 'required|numeric|min:0',

            'unit_measure' => 'required|string|max:20'

        ], [

            'project_id.required' => 'El proyecto es obligatorio.',
            'project_id.exists' => 'El proyecto seleccionado no existe.',

            'block_id.required' => 'La manzana es obligatoria.',
            'block_id.exists' => 'La manzana seleccionada no existe.',

            'quantity.required' => 'La cantidad de lotes es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser mayor a cero.',

            'area.required' => 'El área es obligatoria.',
            'area.numeric' => 'El área debe ser numérica.',

            'unit_measure.required' => 'La unidad de medida es obligatoria.',

        ]);

        try {

            $project = Project::findOrFail($request->project_id);

            $block = Block::findOrFail($request->block_id);

            // =====================================================
            // VALIDAR QUE LA MANZANA PERTENEZCA AL PROYECTO
            // =====================================================

            if ((int) $block->project_id !== (int) $project->id) {

                return response()->json([

                    'status' => 'error',

                    'message' => 'La manzana seleccionada no pertenece al proyecto indicado.'

                ], 422);
            }

            DB::beginTransaction();

            // =====================================================
            // BUSCAR LOTES EXISTENTES DE ESA MISMA MANZANA
            // =====================================================

            $existingNumbers = Lot::where('project_id', $project->id)
                ->where('block_id', $block->id)
                ->pluck('number')
                ->map(function ($n) {

                    // Soporta "01", "1", "L01"
                    return (int) preg_replace('/\D/', '', (string) $n);
                })
                ->toArray();

            $created = 0;

            $skipped = 0;

            // =====================================================
            // GENERAR LOTES
            // =====================================================

            for ($i = 1; $i <= $request->quantity; $i++) {

                // Si el lote ya existe dentro de esa misma manzana, se omite
                if (in_array($i, $existingNumbers)) {

                    $skipped++;

                    continue;
                }

                $lotNumber = str_pad($i, 2, '0', STR_PAD_LEFT);

                // El código técnico es independiente del número físico del lote.
                // Usa el mismo correlativo global e inmutable que el alta individual.
                $code = $this->reserveLotCode($project, $block);

                Lot::create([

                    'project_id' => $project->id,

                    'block_id' => $block->id,

                    'code' => $code,

                    'number' => $lotNumber,

                    'area' => $request->area,

                    'unit_measure' => $request->unit_measure,

                    'cash_price' => 0,

                    'financed_price' => 0,

                    'status' => 'bloqueado',

                    'observation' => 'Lote generado automáticamente.',

                    'created_by' => Auth::id(),

                    'updated_by' => Auth::id()

                ]);

                $created++;
            }

            DB::commit();

            return response()->json([

                'status' => 'success',

                'created' => $created,

                'skipped' => $skipped,

                'message' => 'Lotes generados correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error generating lots: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al generar los lotes.',

                'error' => $e->getMessage()

            ], 500);
        }
    }
    /**
     * Construye únicamente el prefijo legible del código.
     * Ejemplo: "Madrid I" + "MZ A" => "MI-MZA".
     */
    private function buildLotCodePrefix(Project $project, Block $block): string
    {
        $projectName = strtoupper(Str::ascii(trim((string) $project->name)));
        $words = preg_split('/\s+/', $projectName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = '';

        foreach ($words as $word) {
            $initials .= substr($word, 0, 1);
        }

        if ($initials === '') {
            $initials = 'P' . $project->id;
        }

        $blockCode = strtoupper(Str::ascii(trim((string) $block->name)));
        $blockCode = preg_replace('/[^A-Z0-9]+/', '', $blockCode) ?: '';

        if ($blockCode === '') {
            $blockCode = 'MZ' . $block->id;
        }

        return $initials . '-' . $blockCode;
    }

    /**
     * Reserva el siguiente correlativo global bajo bloqueo de BD.
     * Debe ejecutarse dentro de la transacción de generación masiva.
     */
    private function reserveLotCode(Project $project, Block $block): string
    {
        $sequence = DB::table('lot_code_sequences')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            throw new \RuntimeException('No existe el correlativo técnico de lotes. Ejecute la migración pendiente.');
        }

        $prefix = $this->buildLotCodePrefix($project, $block);
        $nextNumber = (int) $sequence->last_number;

        do {
            $nextNumber++;
            $code = $prefix . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        } while (Lot::where('code', $code)->exists());

        DB::table('lot_code_sequences')
            ->where('id', 1)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $code;
    }

}
