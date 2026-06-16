<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Holiday;

use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        return view(
            'admin.holidays.index'
        );
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $holidays = Holiday::with([

            'creator',
            'updater'

        ])
            ->orderBy('date', 'desc')
            ->get();

        return DataTables::of($holidays)

            ->addIndexColumn()

            // =====================================
            // FECHA
            // =====================================

            ->editColumn(
                'date',
                function ($item) {

                    return \Carbon\Carbon::parse(
                        $item->date
                    )->format('d/m/Y');
                }
            )

            // =====================================
            // STATUS
            // =====================================

            ->editColumn(
                'status',
                function ($item) {

                    $colors = [

                        'activo' => 'success',

                        'inactivo' => 'danger'

                    ];

                    $color =
                        $colors[$item->status]
                        ?? 'secondary';

                    return '
                        <span class="badge bg-' . $color . ' text-light rounded-pill px-3 py-2 shadow-sm">
                            ' . ucfirst($item->status) . '
                        </span>
                    ';
                }
            )

            // =====================================
            // ACCIONES
            // =====================================

            ->addColumn(
                'acciones',
                function ($item) {

                    return view(
                        'admin.holidays.partials.acciones',
                        [
                            'holiday' => $item
                        ]
                    )->render();
                }
            )

            ->rawColumns([

                'status',

                'acciones'

            ])

            ->make(true);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $request->validate([

            'date' => [

                'required',
                'date',
                'unique:holidays,date'

            ],

            'description' => [

                'required',
                'string',
                'max:255'

            ],

            'status' => [

                'required',
                'in:activo,inactivo'

            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================
            // AUDITORÍA
            // =====================================

            if (Auth::check()) {

                $data['created_by'] =
                    Auth::id();

                $data['updated_by'] =
                    Auth::id();
            }

            $holiday =
                Holiday::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' =>
                'Feriado registrado correctamente.',

                'data' => $holiday

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error creating holiday: '
                    . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' =>
                'Error al registrar el feriado.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $holiday =
            Holiday::find($id);

        if (!$holiday) {

            return response()->json([

                'status' => 'error',

                'message' =>
                'Feriado no encontrado.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $holiday

        ]);
    }

    /**
     * UPDATE
     */
    public function update(
        Request $request,
        $id
    ) {
        $holiday =
            Holiday::find($id);

        if (!$holiday) {

            return response()->json([

                'status' => 'error',

                'message' =>
                'Feriado no encontrado.'

            ], 404);
        }

        $data = $request->validate([

            'date' => [

                'required',
                'date',
                'unique:holidays,date,' . $holiday->id

            ],

            'description' => [

                'required',
                'string',
                'max:255'

            ],

            'status' => [

                'required',
                'in:activo,inactivo'

            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================
            // AUDITORÍA
            // =====================================

            if (Auth::check()) {

                $data['updated_by'] =
                    Auth::id();
            }

            $holiday->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' =>
                'Feriado actualizado correctamente.',

                'data' =>
                $holiday->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error updating holiday: '
                    . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' =>
                'Error al actualizar el feriado.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(
        Holiday $holiday
    ) {
        DB::beginTransaction();

        try {

            $holiday->delete();

            DB::commit();

            return response()->json([

                'message' =>
                'Feriado eliminado correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                'Error al eliminar el feriado.',

                'error' =>
                $e->getMessage()

            ], 500);
        }
    }
}
