<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CustomerPortalAuthController extends Controller
{
    public function login(Request $request)
    {
        $dni = is_string($request->input('dni')) ? trim($request->input('dni')) : '';
        $password = $request->input('password');
        $customers = preg_match('/^[0-9]{8}$/D', $dni)
            ? CustomerPortalAccount::customersForDni($dni)->limit(2)->get() : collect();
        $customer = $customers->count() === 1 ? $customers->first() : null;
        $account = $customer ? CustomerPortalAccount::where('customer_id', $customer->id)->first() : null;
        // A valid bcrypt dummy hash keeps unknown accounts on the password verification path.
        $valid = Hash::check(is_string($password) && strlen($password) <= 1024 ? $password : '',
            $account?->password ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $valid || ! $account?->is_active || ! $customer?->status) {
            return response()->json(['message' => 'Los datos ingresados no son correctos.'], 422);
        }
        $request->session()->regenerate();
        $request->session()->put('customer_portal', ['account_id' => $account->id, 'version' => hash('sha256', $account->password)]);
        $account->update(['last_login_at' => now()]);
        return response()->json(['must_change_password' => $account->must_change_password, 'csrf_token' => csrf_token(), 'message' => 'Bienvenido a tu portal.']);
    }

    public function session(Request $request)
    {
        return response()->json(['must_change_password' => $request->attributes->get('portal_account')->must_change_password]);
    }

    public function changePassword(Request $request)
    {
        $account = $request->attributes->get('portal_account');
        abort_unless($account->must_change_password, 403);
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)->letters()->numbers(),
                function ($attribute, $value, $fail) use ($account) {
                    if ($value === trim($account->customer->document_number)) {
                        $fail('Elige una contraseña diferente de tu DNI.');
                    }
                }],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Usa al menos 8 caracteres con letras y números, distintos de tu DNI, y confirma la misma contraseña.'], 422);
        }
        $account->update(['password' => Hash::make($request->string('password')->toString()), 'must_change_password' => false]);
        $request->session()->regenerate();
        $request->session()->put('customer_portal.version', hash('sha256', $account->password));
        return response()->json(['must_change_password' => false, 'csrf_token' => csrf_token()]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('customer_portal');
        $request->session()->regenerate();
        return response()->json(['message' => 'Sesión cerrada.', 'csrf_token' => csrf_token()]);
    }
}
