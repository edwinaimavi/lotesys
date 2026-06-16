<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        $companies = Company::where('status', 1)
            ->orderBy('business_name')
            ->get();

        return view('admin.projects.index', compact('companies'));
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $projects = Project::with(
            'company',
            'creator',
            'updater',
            'blocks',
            'lots'
        )
            ->where('status', '!=', -1)
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($projects)

            ->addIndexColumn()

            ->addColumn('company', function ($project) {

                return $project->company->business_name ?? '—';
            })

            ->editColumn('start_date', function ($project) {

                return $project->start_date
                    ? date('d/m/Y', strtotime($project->start_date))
                    : '—';
            })

            ->editColumn('status', function ($project) {

                return $project->status == 1

                    ? '<span class="badge bg-success text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> Activo
                       </span>'

                    : '<span class="badge bg-danger text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-times-circle me-1"></i> Inactivo
                       </span>';
            })

            ->addColumn('acciones', function ($project) {

                return view('admin.projects.partials.acciones', compact('project'))->render();
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

            'company_id' => [
                'required',
                'exists:companies,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],



            'description' => [
                'nullable',
                'string'
            ],

            'address' => [
                'nullable',
                'string',
                'max:255'
            ],

            'district' => [
                'nullable',
                'string',
                'max:150'
            ],

            'province' => [
                'nullable',
                'string',
                'max:150'
            ],

            'department' => [
                'nullable',
                'string',
                'max:150'
            ],

            'total_area' => [
                'nullable',
                'numeric'
            ],

            'registry_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:projects,registry_number'
            ],

            'start_date' => [
                'nullable',
                'date'
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'company_id.required' => 'La empresa es obligatoria.',

            'company_id.exists' => 'La empresa seleccionada no existe.',

            'name.required' => 'El nombre del proyecto es obligatorio.',

            'code.required' => 'El código es obligatorio.',

            'code.unique' => 'El código ya está registrado.',

            'registry_number.unique' => 'El número de partida ya está registrado.',

            'start_date.date' => 'La fecha de inicio no es válida.'

        ]);

        $data['name'] = ucwords(strtolower(trim($data['name'])));

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            // =====================================================
            // GENERAR CÓDIGO AUTOMÁTICO
            // =====================================================

            $lastProject = Project::orderBy('id', 'desc')->first();

            $nextNumber = 1;

            if ($lastProject) {

                $lastCode = $lastProject->code;

                $number = (int) filter_var($lastCode, FILTER_SANITIZE_NUMBER_INT);

                $nextNumber = $number + 1;
            }

            $data['code'] = 'PRJ' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // =====================================================
            // VALIDAR DUPLICIDAD
            // =====================================================

            $exists = Project::where('company_id', $data['company_id'])
                ->where('name', trim($data['name']))
                ->where('status', '!=', -1)
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'name' => [
                            'El proyecto ya existe para esta empresa.'
                        ]
                    ]

                ], 422);
            }

            $project = Project::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Proyecto registrado correctamente.',

                'data' => $project

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error creating project: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar el proyecto.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $project = Project::find($id);

        if (! $project) {

            return response()->json([

                'status' => 'error',

                'message' => 'Proyecto no encontrado.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $project

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (! $project) {

            return response()->json([

                'status' => 'error',

                'message' => 'Proyecto no encontrado.'

            ], 404);
        }

        $data = $request->validate([

            'company_id' => [
                'required',
                'exists:companies,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('projects', 'code')->ignore($project->id)
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'address' => [
                'nullable',
                'string',
                'max:255'
            ],

            'district' => [
                'nullable',
                'string',
                'max:150'
            ],

            'province' => [
                'nullable',
                'string',
                'max:150'
            ],

            'department' => [
                'nullable',
                'string',
                'max:150'
            ],

            'total_area' => [
                'nullable',
                'numeric'
            ],

            'registry_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('projects', 'registry_number')->ignore($project->id)
            ],

            'start_date' => [
                'nullable',
                'date'
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'company_id.required' => 'La empresa es obligatoria.',

            'company_id.exists' => 'La empresa seleccionada no existe.',

            'name.required' => 'El nombre del proyecto es obligatorio.',

            'code.required' => 'El código es obligatorio.',

            'code.unique' => 'El código ya está registrado.',

            'registry_number.unique' => 'El número de partida ya está registrado.',

            'start_date.date' => 'La fecha de inicio no es válida.'

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            // =====================================================
            // VALIDAR DUPLICIDAD
            // =====================================================

            $exists = Project::where('company_id', $data['company_id'])
                ->where('name', trim($data['name']))
                ->where('id', '!=', $project->id)
                ->where('status', '!=', -1)
                ->exists();

            if ($exists) {

                return response()->json([

                    'errors' => [
                        'name' => [
                            'El proyecto ya existe para esta empresa.'
                        ]
                    ]

                ], 422);
            }

            $project->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Proyecto actualizado correctamente.',

                'data' => $project->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error updating project: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar el proyecto.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(Project $project)
    {
        $project->update([

            'status' => -1

        ]);

        return response()->json([

            'message' => 'Proyecto eliminado correctamente.'

        ]);
    }

    public function generateCode()
    {
        $lastProject = Project::orderBy('id', 'desc')->first();

        $nextNumber = 1;

        if ($lastProject) {

            $lastCode = $lastProject->code;

            $number = (int) filter_var($lastCode, FILTER_SANITIZE_NUMBER_INT);

            $nextNumber = $number + 1;
        }

        $code = 'PRJ' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'code' => $code
        ]);
    }
}
