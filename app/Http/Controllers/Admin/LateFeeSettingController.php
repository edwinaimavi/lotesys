<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\LateFeeSetting;

use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LateFeeSettingController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        return view(
            'admin.lateFeeSettings.index'
        );
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $lateFeeSettings = LateFeeSetting::with([

            'creator',
            'updater'

        ])
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($lateFeeSettings)

            ->addIndexColumn()

            // =====================================
            // MORA DIARIA
            // =====================================

            ->editColumn(
                'daily_late_fee',
                function ($item) {

                    return 'S/ ' .
                        number_format(
                            $item->daily_late_fee,
                            2
                        );
                }
            )

            // =====================================
            // MORA MÁXIMA
            // =====================================

            ->editColumn(
                'max_late_fee',
                function ($item) {

                    return $item->max_late_fee

                        ? 'S/ ' .
                        number_format(
                            $item->max_late_fee,
                            2
                        )

                        : '—';
                }
            )

            // =====================================
            // DOMINGOS
            // =====================================

            ->editColumn(
                'apply_sundays',
                function ($item) {

                    return $item->apply_sundays

                        ? '<span class="badge bg-success text-light px-3 py-2 rounded-pill">
                                SI
                           </span>'

                        : '<span class="badge bg-danger text-light px-3 py-2 rounded-pill">
                                NO
                           </span>';
                }
            )

            // =====================================
            // FERIADOS
            // =====================================

            ->editColumn(
                'apply_holidays',
                function ($item) {

                    return $item->apply_holidays

                        ? '<span class="badge bg-success text-light px-3 py-2 rounded-pill">
                                SI
                           </span>'

                        : '<span class="badge bg-danger text-light px-3 py-2 rounded-pill">
                                NO
                           </span>';
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
                        'admin.lateFeeSettings.partials.acciones',
                        [
                            'lateFeeSetting' => $item
                        ]
                    )->render();
                }
            )

            ->rawColumns([

                'apply_sundays',

                'apply_holidays',

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

            'grace_days' => [

                'required',
                'integer',
                'min:0'

            ],

            'daily_late_fee' => [

                'required',
                'numeric',
                'min:0'

            ],

            'max_late_fee' => [

                'nullable',
                'numeric',
                'min:0'

            ],

            'apply_sundays' => [

                'nullable',
                'boolean'

            ],

            'apply_holidays' => [

                'nullable',
                'boolean'

            ],

            'status' => [

                'required',
                'in:activo,inactivo'

            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================
            // SWITCHES
            // =====================================

            $data['apply_sundays'] =
                $request->apply_sundays ? 1 : 0;

            $data['apply_holidays'] =
                $request->apply_holidays ? 1 : 0;

            // =====================================
            // AUDITORÍA
            // =====================================

            if (Auth::check()) {

                $data['created_by'] =
                    Auth::id();

                $data['updated_by'] =
                    Auth::id();
            }

            $lateFeeSetting =
                LateFeeSetting::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' =>
                'Configuración registrada correctamente.',

                'data' => $lateFeeSetting

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error creating late fee setting: '
                    . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' =>
                'Error al registrar la configuración.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $lateFeeSetting =
            LateFeeSetting::find($id);

        if (!$lateFeeSetting) {

            return response()->json([

                'status' => 'error',

                'message' =>
                'Configuración no encontrada.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $lateFeeSetting

        ]);
    }

    /**
     * UPDATE
     */
    public function update(
        Request $request,
        $id
    ) {
        $lateFeeSetting =
            LateFeeSetting::find($id);

        if (!$lateFeeSetting) {

            return response()->json([

                'status' => 'error',

                'message' =>
                'Configuración no encontrada.'

            ], 404);
        }

        $data = $request->validate([

            'grace_days' => [

                'required',
                'integer',
                'min:0'

            ],

            'daily_late_fee' => [

                'required',
                'numeric',
                'min:0'

            ],

            'max_late_fee' => [

                'nullable',
                'numeric',
                'min:0'

            ],

            'apply_sundays' => [

                'nullable',
                'boolean'

            ],

            'apply_holidays' => [

                'nullable',
                'boolean'

            ],

            'status' => [

                'required',
                'in:activo,inactivo'

            ]

        ]);

        try {

            DB::beginTransaction();

            // =====================================
            // SWITCHES
            // =====================================

            $data['apply_sundays'] =
                $request->apply_sundays ? 1 : 0;

            $data['apply_holidays'] =
                $request->apply_holidays ? 1 : 0;

            // =====================================
            // AUDITORÍA
            // =====================================

            if (Auth::check()) {

                $data['updated_by'] =
                    Auth::id();
            }

            $lateFeeSetting->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' =>
                'Configuración actualizada correctamente.',

                'data' =>
                $lateFeeSetting->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error updating late fee setting: '
                    . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' =>
                'Error al actualizar la configuración.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(
        LateFeeSetting $lateFeeSetting
    ) {
        DB::beginTransaction();

        try {

            $lateFeeSetting->delete();

            DB::commit();

            return response()->json([

                'message' =>
                'Configuración eliminada correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'message' =>
                'Error al eliminar la configuración.',

                'error' =>
                $e->getMessage()

            ], 500);
        }
    }
}
