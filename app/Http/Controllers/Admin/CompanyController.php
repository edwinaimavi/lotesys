<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * INDEX
     */
    public function index()
    {
        return view('admin.companies.index');
    }

    /**
     * LIST DATATABLE
     */
    public function list()
    {
        $companies = Company::where('status', '!=', -1)
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($companies)

            ->addIndexColumn()

            ->editColumn('status', function ($company) {

                return $company->status == 1

                    ? '<span class="badge bg-success text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> Active
                       </span>'

                    : '<span class="badge bg-danger text-light rounded-pill px-3 py-2 shadow-sm">
                            <i class="fas fa-times-circle me-1"></i> Inactive
                       </span>';
            })

            ->addColumn('acciones', function ($company) {

                return view('admin.companies.partials.acciones', compact('company'))->render();

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

            'business_name' => [
                'required',
                'string',
                'max:255'
            ],

            'trade_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'ruc' => [
                'required',
                'digits:11',
                Rule::unique('companies', 'ruc')
            ],

            'address' => [
                'nullable',
                'string',
                'max:255'
            ],

            'phone' => [
                'nullable',
                'regex:/^[0-9]{9,15}$/'
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('companies', 'email')
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'business_name.required' => 'Business name is required.',

            'ruc.required' => 'RUC is required.',

            'ruc.digits' => 'RUC must contain exactly 11 digits.',

            'ruc.unique' => 'This RUC is already registered.',

            'email.email' => 'Enter a valid email address.',

            'email.unique' => 'This email is already in use.',

            'phone.regex' => 'Phone number must contain only numbers.'

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['created_by'] = Auth::id();

                $data['updated_by'] = Auth::id();
            }

            $company = Company::create($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Company created successfully.',

                'data' => $company

            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error creating company: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error creating company.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $company = Company::find($id);

        if (! $company) {

            return response()->json([

                'status' => 'error',

                'message' => 'Company not found.'

            ], 404);
        }

        return response()->json([

            'status' => 'success',

            'data' => $company

        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (! $company) {

            return response()->json([

                'status' => 'error',

                'message' => 'Company not found.'

            ], 404);
        }

        $data = $request->validate([

            'business_name' => [
                'required',
                'string',
                'max:255'
            ],

            'trade_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'ruc' => [
                'required',
                'digits:11',
                Rule::unique('companies', 'ruc')->ignore($company->id)
            ],

            'address' => [
                'nullable',
                'string',
                'max:255'
            ],

            'phone' => [
                'nullable',
                'regex:/^[0-9]{9,15}$/'
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('companies', 'email')->ignore($company->id)
            ],

            'status' => [
                'required',
                'in:0,1'
            ]

        ], [

            'business_name.required' => 'Business name is required.',

            'ruc.required' => 'RUC is required.',

            'ruc.digits' => 'RUC must contain exactly 11 digits.',

            'ruc.unique' => 'This RUC is already registered.',

            'email.email' => 'Enter a valid email address.',

            'email.unique' => 'This email is already in use.',

            'phone.regex' => 'Phone number must contain only numbers.'

        ]);

        try {

            DB::beginTransaction();

            if (Auth::check()) {

                $data['updated_by'] = Auth::id();
            }

            $company->update($data);

            DB::commit();

            return response()->json([

                'status' => 'success',

                'message' => 'Company updated successfully.',

                'data' => $company->fresh()

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error updating company: ' . $e->getMessage());

            return response()->json([

                'status' => 'error',

                'message' => 'Error updating company.',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy(Company $company)
    {
        $company->update([

            'status' => -1

        ]);

        return response()->json([

            'message' => 'Company deleted successfully.'

        ]);
    }
}