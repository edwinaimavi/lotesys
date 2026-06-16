<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Bank;

use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        return view('admin.banks.index');
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $banks = Bank::with([
            'creator',
            'updater'
        ])
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($banks)

            ->addIndexColumn()

            ->editColumn('currency', function ($bank) {

                return $bank->currency;
            })

            ->editColumn('status', function ($bank) {

                $colors = [

                    'activo' => 'success',

                    'inactivo' => 'danger'

                ];

                $color = $colors[$bank->status] ?? 'secondary';

                return '
                    <span class="badge bg-' . $color . ' text-light rounded-pill px-3 py-2 shadow-sm">
                        ' . ucfirst($bank->status) . '
                    </span>
                ';
            })

            ->addColumn('acciones', function ($bank) {

                return view(
                    'admin.banks.partials.acciones',
                    compact('bank')
                )->render();
            })

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

            'bank_name' => [
                'required',
                'string',
                'max:255'
            ],

            'currency' => [
                'required',
                'in:PEN,USD'
            ],

            'account_number' => [
                'required',
                'string',
                'max:255'
            ],

            'cci' => [
                'required',
                'string',
                'max:255'
            ],

            'account_holder' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:activo,inactivo'
            ]

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            $bank = Bank::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Banco registrado correctamente.',

                'data' => $bank

            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error creating bank: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al registrar el banco.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $bank = Bank::find($id);

        if (!$bank) {

            return response()->json([

                'status' => 'error',

                'message' => 'Banco no encontrado.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $bank

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $bank = Bank::find($id);

        if (!$bank) {

            return response()->json([

                'status' => 'error',

                'message' => 'Banco no encontrado.'

            ], 404);
        }

        $data = $request->validate([

            'bank_name' => [
                'required',
                'string',
                'max:255'
            ],

            'currency' => [
                'required',
                'in:PEN,USD'
            ],

            'account_number' => [
                'required',
                'string',
                'max:255'
            ],

            'cci' => [
                'required',
                'string',
                'max:255'
            ],

            'account_holder' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:activo,inactivo'
            ]

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            $bank->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Banco actualizado correctamente.',

                'data' => $bank->fresh()

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Error updating bank: ' . $e->getMessage()
            );

            return response()->json([

                'status' => 'error',

                'message' => 'Error al actualizar el banco.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(Bank $bank)
    {
        DB::beginTransaction();

        try {

            $bank->delete();

            DB::commit();

            return response()->json([

                'message' => 'Banco eliminado correctamente.'

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'message' => 'Error al eliminar el banco.',

                'error' => $e->getMessage()

            ], 500);
        }
    }
}
