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
    public function generateLots(Request $request)
    {
        $request->validate([

            'project_id' => 'required|exists:projects,id',

            'block_id' => 'required|exists:blocks,id',

            'quantity' => 'required|integer|min:1',

            'area' => 'required|numeric|min:0',

            'unit_measure' => 'required|string|max:20'

        ]);

        try {

            DB::beginTransaction();

            $project = Project::findOrFail($request->project_id);

            $block = Block::findOrFail($request->block_id);

            // =====================================================
            // GENERAR INICIALES DEL PROYECTO
            // =====================================================

            $words = explode(' ', strtoupper($project->name));

            $initials = '';

            foreach ($words as $word) {

                if (!empty($word)) {

                    $initials .= substr($word, 0, 1);
                }
            }

            // =====================================================
            // MANZANA
            // =====================================================

            $blockName = strtoupper(str_replace(' ', '', $block->name));

            // =====================================================
            // BUSCAR EXISTENTES
            // =====================================================

            $existingNumbers = Lot::where('project_id', $project->id)
                ->where('block_id', $block->id)
                ->pluck('number')
                ->map(function ($n) {

                    return intval($n);
                })
                ->toArray();

            $created = 0;

            $skipped = 0;

            // =====================================================
            // GENERAR LOTES
            // =====================================================

            for ($i = 1; $i <= $request->quantity; $i++) {

                // YA EXISTE
                if (in_array($i, $existingNumbers)) {

                    $skipped++;

                    continue;
                }

                $lotNumber = str_pad($i, 2, '0', STR_PAD_LEFT);

                // EJEMPLO:
                // RLPMZA-L01

                $code = $initials
                    . $blockName
                    . '-L'
                    . $lotNumber;

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
