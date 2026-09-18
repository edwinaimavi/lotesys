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
use Illuminate\Support\Str;

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

            ->addColumn('company', function ($lot) {

                return $lot->project->company->business_name ?? '—';
            })

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


    /**
     * PREVISUALIZAR FORMATO DEL CÓDIGO.
     *
     * El correlativo real se reserva únicamente dentro de store(), bajo
     * transacción y bloqueo. Este endpoint NO consume números.
     */
    public function generateCode(Request $request)
    {
        $project = Project::find($request->project_id);

        $block = Block::where('id', $request->block_id)
            ->where('project_id', $request->project_id)
            ->first();

        if (! $project || ! $block) {
            return response()->json([
                'code' => ''
            ]);
        }

        return response()->json([
            'code' => $this->buildLotCodePrefix($project, $block) . '-######'
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

            // El código NO se acepta desde el navegador.
            // Se genera y reserva exclusivamente en el backend.

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
            'number.required' => 'El número de lote es obligatorio.',
            'area.required' => 'El área es obligatoria.',
            'area.numeric' => 'El área debe ser numérica.',
            'cash_price.required' => 'El precio contado es obligatorio.',
            'cash_price.numeric' => 'El precio contado debe ser numérico.',
            'financed_price.required' => 'El precio financiado es obligatorio.',
            'financed_price.numeric' => 'El precio financiado debe ser numérico.',
            'status.required' => 'El estado es obligatorio.'
        ]);

        $project = Project::find($data['project_id']);

        $block = Block::where('id', $data['block_id'])
            ->where('project_id', $data['project_id'])
            ->first();

        if (! $block) {
            return response()->json([
                'errors' => [
                    'block_id' => [
                        'La manzana seleccionada no pertenece al proyecto.'
                    ]
                ]
            ], 422);
        }

        // =====================================================
        // VALIDAR DUPLICIDAD DEL NÚMERO FÍSICO DEL LOTE
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

            // El correlativo es GLOBAL y persistente.
            // No depende del número físico del lote y no se reutiliza
            // aunque un lote sea eliminado posteriormente.
            $data['code'] = $this->reserveLotCode($project, $block);

            if (Auth::check()) {
                $data['created_by'] = Auth::id();
                $data['updated_by'] = Auth::id();
            }

            $lot = Lot::create($data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Lote registrado correctamente. Código: ' . $lot->code,
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

            // El código es inmutable: se conserva el asignado al crear.

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
            'number.required' => 'El número de lote es obligatorio.',
            'area.required' => 'El área es obligatoria.',
            'area.numeric' => 'El área debe ser numérica.',
            'cash_price.required' => 'El precio contado es obligatorio.',
            'cash_price.numeric' => 'El precio contado debe ser numérico.',
            'financed_price.required' => 'El precio financiado es obligatorio.',
            'financed_price.numeric' => 'El precio financiado debe ser numérico.',
            'status.required' => 'El estado es obligatorio.'
        ]);

        $block = Block::where('id', $data['block_id'])
            ->where('project_id', $data['project_id'])
            ->first();

        if (! $block) {
            return response()->json([
                'errors' => [
                    'block_id' => [
                        'La manzana seleccionada no pertenece al proyecto.'
                    ]
                ]
            ], 422);
        }

        // =====================================================
        // VALIDAR DUPLICIDAD DEL NÚMERO FÍSICO DEL LOTE
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

            // $data no contiene "code": el identificador técnico no cambia.
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


    /**
     * Construye únicamente el prefijo legible del código.
     * Ejemplo: "Madrid I" + "MZ B" => "MI-MZB".
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
     * Debe ejecutarse dentro de una transacción abierta.
     */
    private function reserveLotCode(Project $project, Block $block): string
    {
        $sequence = DB::table('lot_code_sequences')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            // Fallar de forma segura es preferible a reiniciar el contador:
            // reiniciarlo podría reutilizar un código de un lote eliminado.
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

    public function getProjectsByCompany($companyId)
    {
        $projects = Project::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }
}
