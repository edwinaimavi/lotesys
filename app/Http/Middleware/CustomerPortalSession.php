<?php

namespace App\Http\Middleware;

use App\Models\CustomerPortalAccount;
use Closure;
use Illuminate\Http\Request;

class CustomerPortalSession
{
    public function handle(Request $request, Closure $next, string $stage = 'ready')
    {
        $account = CustomerPortalAccount::with('customer')->find($request->session()->get('customer_portal.account_id'));
        if (! $account || ! $account->is_active || ! $account->customer?->status
            || ! hash_equals(hash('sha256', $account->password), (string) $request->session()->get('customer_portal.version'))) {
            $request->session()->forget('customer_portal');
            return response()->json(['message' => 'Tu sesión ha finalizado. Ingresa nuevamente.'], 401);
        }
        if ($stage === 'ready' && $account->must_change_password) {
            return response()->json(['message' => 'Primero protege tu cuenta.', 'must_change_password' => true], 403);
        }
        $request->attributes->set('portal_account', $account);
        return $next($request);
    }
}
