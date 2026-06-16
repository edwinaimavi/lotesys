<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Block;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LotController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $projects = Project::where('status', 1)
            ->orderBy('name')
            ->get();

        $companies = \App\Models\Company::where('status', 1)
            ->orderBy('business_name')
            ->get();

        return view(
            'admin.lots.index',
            compact('projects', 'companies')
        );
    }

    /**
     * LIST DATATABLE
     */
    public function list(Request $request)
    {
        $query = Lot::with(
            'project.company',
            'block',
            'creator',
            'updater'
        );


        // =====================================================
        // FILTRO EMPRESA
        // =====================================================

        if ($request->company_id) {

            $query->whereHas('project', function ($q) use ($request) {

                $q->where('company_id', $request->company_id);
            });
        }

        // =====================================================
        // FILTRO PROYECTO
        // =====================================================

        if ($request->project_id) {

            $query->where('project_id', $request->project_id);
        }

        // =====================================================
        // FILTRO MANZANA
        // =====================================================

        if ($request->block_id) {

            $query->where('block_id', $request->block_id);
        }

        // =====================================================
        // FILTRO LOTE
        // =====================================================

        if ($request->lot_number) {

            $query->where('number', 'LIKE', '%' . $request->lot_number . '%');
        }

        $lots = $query
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($lots)

            ->addIndexColumn()

            ->addColumn('project', function ($lot) {

                return $lot->project->name ?? '—';
            })

            ->addColumn('block', function ($lot) {

                return $lot->block->name ?? '—';
            })

            ->editColumn('area', function ($lot) {

                return number_format($lot->area, 2)
                    . ' '
                    . $lot->unit_measure;
            })

            ->editColumn('cash_price', function ($lot) {

                return 'S/ '
                    . number_format($lot->cash_price, 2);
            })

            ->editColumn('status', function ($lot) {

                $status = $lot->status;

                $colors = [

                    'disponible' => 'success',

                    'separado' => 'warning',

                    'vendido' => 'primary',

                    'rescindido' => 'danger',

                    'bloqueado' => 'dark'

                ];

                $color = $colors[$status] ?? 'secondary';

                return '
                    <span class="badge bg-' . $color . ' text-light rounded-pill px-3 py-2 shadow-sm">
                        ' . ucfirst($status) . '
                    </span>
                ';
            })

            ->addColumn('acciones', function ($lot) {

                return view('admin.lots.partials.acciones', compact('lot'))->render();
            })

            ->rawColumns(['status', 'acciones'])

            ->make(true);
    }

    /**
     * OBTENER MANZANAS POR PROYECTO
     */
    public function getBlocks($projectId)
    {
        $blocks = Block::where('project_id', $projectId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json($blocks);
    }


    public function generateCode(Request $request)
    {
        $project = Project::find($request->project_id);

        $block = Block::find($request->block_id);

        if (!$project || !$block) {

            return response()->json([
                'code' => ''
            ]);
        }

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
        // LIMPIAR MANZANA
        // =====================================================

        $blockName = strtoupper(trim($block->name));

        // MZ A -> MZA
        $blockName = str_replace(' ', '', $blockName);

        // =====================================================
        // BASE
        // =====================================================

        $baseCode = $initials . $blockName . '-L';

        // =====================================================
        // BUSCAR ÚLTIMO
        // =====================================================

        $lastLot = Lot::where('code', 'LIKE', $baseCode . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastLot) {

            preg_match('/L(\d+)$/', $lastLot->code, $matches);

            if (isset($matches[1])) {

                $nextNumber = intval($matches[1]) + 1;
            }
        }

        $code = $baseCode . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        return response()->json([
            'code' => $code
        ]);
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

            'block_id' => [
                'required',
                'exists:blocks,id'
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                'unique:lots,code'
            ],

            'number' => [
                'required',
                'string',
                'max:100'
            ],

            'area' => [
                'required',
                'numeric',
                'min:0'
            ],

            'unit_measure' => [
                'required',
                'string',
                'max:20'
            ],

            'cash_price' => [
                'required',
                'numeric',
                'min:0'
            ],
            'financed_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'north_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'south_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'east_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'west_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'status' => [
                'required',
                'in:disponible,separado,vendido,rescindido,bloqueado'
            ],

            'observation' => [
                'nullable',
                'string'
            ]

        ], [

            'project_id.required' => 'El proyecto es obligatorio.',

            'block_id.required' => 'La manzana es obligatoria.',

            'code.required' => 'El código es obligatorio.',

            'code.unique' => 'El código ya existe.',

            'number.required' => 'El número de lote es obligatorio.',

            'area.required' => 'El área es obligatoria.',

            'area.numeric' => 'El área debe ser numérica.',

            'cash_price.required' => 'El precio contado es obligatorio.',

            'cash_price.numeric' => 'El precio contado debe ser numérico.',
            'financed_price.required' => 'El precio financiado es obligatorio.',

            'financed_price.numeric' => 'El precio financiado debe ser numérico.',
            'status.required' => 'El estado es obligatorio.'

        ]);


        // =====================================================
        // VALIDAR DUPLICIDAD DE LOTE
        // =====================================================

        $exists = Lot::where('project_id', $data['project_id'])
            ->where('block_id', $data['block_id'])
            ->where('number', $data['number'])
            ->exists();

        if ($exists) {

            return response()->json([

                'errors' => [

                    'number' => [
                        'El número de lote ya existe en esta manzana.'
                    ]

                ]

            ], 422);
        }

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            $lot = Lot::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Lote registrado correctamente.',

                'data' => $lot

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error creating lot: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar el lote.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $lot = Lot::find($id);

        if (! $lot) {

            return response()->json([

                'status' => 'error',

                'message' => 'Lote no encontrado.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $lot

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $lot = Lot::find($id);

        if (! $lot) {

            return response()->json([

                'status' => 'error',

                'message' => 'Lote no encontrado.'

            ], 404);
        }

        $data = $request->validate([

            'project_id' => [
                'required',
                'exists:projects,id'
            ],

            'block_id' => [
                'required',
                'exists:blocks,id'
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                'unique:lots,code,' . $lot->id
            ],

            'number' => [
                'required',
                'string',
                'max:100'
            ],

            'area' => [
                'required',
                'numeric',
                'min:0'
            ],

            'unit_measure' => [
                'required',
                'string',
                'max:20'
            ],

            'cash_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'financed_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'north_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'south_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'east_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'west_boundary' => [
                'nullable',
                'string',
                'max:255'
            ],

            'status' => [
                'required',
                'in:disponible,separado,vendido,rescindido,bloqueado'
            ],

            'observation' => [
                'nullable',
                'string'
            ]

        ], [

            'project_id.required' => 'El proyecto es obligatorio.',

            'block_id.required' => 'La manzana es obligatoria.',

            'code.required' => 'El código es obligatorio.',

            'code.unique' => 'El código ya existe.',

            'number.required' => 'El número de lote es obligatorio.',

            'area.required' => 'El área es obligatoria.',

            'area.numeric' => 'El área debe ser numérica.',

            'cash_price.required' => 'El precio contado es obligatorio.',

            'cash_price.numeric' => 'El precio contado debe ser numérico.',

            'financed_price.required' => 'El precio financiado es obligatorio.',

            'financed_price.numeric' => 'El precio financiado debe ser numérico.',

            'status.required' => 'El estado es obligatorio.'

        ]);

        // =====================================================
        // VALIDAR DUPLICIDAD DE LOTE
        // =====================================================

        $exists = Lot::where('project_id', $data['project_id'])
            ->where('block_id', $data['block_id'])
            ->where('number', $data['number'])
            ->where('id', '!=', $lot->id)
            ->exists();

        if ($exists) {

            return response()->json([

                'errors' => [

                    'number' => [
                        'El número de lote ya existe en esta manzana.'
                    ]

                ]

            ], 422);
        }

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            $lot->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Lote actualizado correctamente.',

                'data' => $lot->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error updating lot: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar el lote.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(Lot $lot)
    {
        $lot->delete();

        return response()->json([

            'message' => 'Lote eliminado correctamente.'

        ]);
    }


    public function getProjectsByCompany($companyId)
    {
        $projects = Project::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }
}
