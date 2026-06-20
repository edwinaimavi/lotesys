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

class BlockController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $projects = Project::where('status', 1)
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
            'project',
            'creator',
            'updater',
            'lots'
        )
            ->where('status', '!=', -1)
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($blocks)

            ->addIndexColumn()

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
            // MANZANA
            // Ejemplo: MZ A => MZA
            // =====================================================

            $blockName = strtoupper(trim($block->name));

            $blockName = str_replace(' ', '', $blockName);

            $blockName = preg_replace('/[^A-Z0-9]/', '', $blockName);

            // =====================================================
            // PREFIJO DEL PROYECTO
            //
            // IMPORTANTE:
            // 1. Si el proyecto ya tiene lotes, reutiliza el prefijo existente.
            //    Ejemplo: Madrid II ya tiene MIMZB-L21 => seguirá usando MI.
            //
            // 2. Si el proyecto no tiene lotes, genera prefijo nuevo.
            //    Ejemplo: Madrid I => M1
            //             Madrid II => M2
            // =====================================================

            $existingPrefix = null;

            $existingProjectCodes = Lot::where('project_id', $project->id)
                ->whereNotNull('code')
                ->orderBy('id', 'asc')
                ->pluck('code');

            foreach ($existingProjectCodes as $existingCode) {

                $existingCode = strtoupper(trim($existingCode));

                /*
             * Detecta códigos como:
             * MIMZA-L01   => prefijo MI
             * MIMZB-L21   => prefijo MI
             * M1MZA-L01   => prefijo M1
             * RLPMZA-L01  => prefijo RLP
             */
                if (preg_match('/^(.+?)(MZ[A-Z0-9]+)-L[0-9]+$/', $existingCode, $matches)) {

                    $existingPrefix = $matches[1];

                    break;
                }
            }

            if ($existingPrefix) {

                $initials = $existingPrefix;
            } else {

                // =====================================================
                // GENERAR PREFIJO NUEVO DESDE EL NOMBRE DEL PROYECTO
                // Madrid I   => M1
                // Madrid II  => M2
                // Madrid III => M3
                // Residencial Las Palmeras => RLP
                // =====================================================

                $projectName = strtoupper(trim($project->name));

                $projectName = preg_replace('/\s+/', ' ', $projectName);

                $words = explode(' ', $projectName);

                $romanMap = [
                    'I' => '1',
                    'II' => '2',
                    'III' => '3',
                    'IV' => '4',
                    'V' => '5',
                    'VI' => '6',
                    'VII' => '7',
                    'VIII' => '8',
                    'IX' => '9',
                    'X' => '10',
                ];

                $initials = '';

                foreach ($words as $word) {

                    $word = trim($word);

                    if ($word === '') {
                        continue;
                    }

                    if (isset($romanMap[$word])) {

                        $initials .= $romanMap[$word];
                    } else {

                        $initials .= substr($word, 0, 1);
                    }
                }

                $initials = preg_replace('/[^A-Z0-9]/', '', $initials);

                if ($initials === '') {
                    $initials = 'P' . $project->id;
                }
            }

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

                /*
             * Ejemplos:
             *
             * Madrid II existente:
             * MI + MZB + L21 = MIMZB-L21
             *
             * Madrid I nuevo:
             * M1 + MZA + L01 = M1MZA-L01
             */
                $code = $initials
                    . $blockName
                    . '-L'
                    . $lotNumber;

                // =====================================================
                // VALIDACIÓN EXTRA GLOBAL
                //
                // Si el código ya existe en cualquier proyecto,
                // agregamos el ID del proyecto para evitar choque.
                // No modifica códigos antiguos.
                // =====================================================

                if (Lot::where('code', $code)->exists()) {

                    $code = $initials
                        . 'P'
                        . $project->id
                        . $blockName
                        . '-L'
                        . $lotNumber;
                }

                // Si incluso con el ID del proyecto existe, se omite
                if (Lot::where('code', $code)->exists()) {

                    $skipped++;

                    continue;
                }

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
}
