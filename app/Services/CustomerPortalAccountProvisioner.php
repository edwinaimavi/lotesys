<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPortalAccount;
use App\Models\Sale;
use Illuminate\Support\Facades\Hash;

class CustomerPortalAccountProvisioner
{
    public function ensureForCustomer(Customer $customer): ?CustomerPortalAccount
    {
        $dni = trim($customer->document_number ?? '');
        if (! $customer->status || $customer->document_type !== 'DNI'
            || ! preg_match('/^[0-9]{8}$/D', $dni)
            || CustomerPortalAccount::customersForDni($dni)->count() !== 1
            || ! Sale::where('customer_id', $customer->id)->whereIn('status', ['activo', 'finalizado'])->exists()) {
            return null;
        }

        // Existing accounts remain untouched, without computing another hash.
        $account = CustomerPortalAccount::where('customer_id', $customer->id)->first();
        if ($account) {
            return $account;
        }

        return CustomerPortalAccount::firstOrCreate(['customer_id' => $customer->id], [
            'password' => Hash::make($dni),
            'must_change_password' => true,
            'is_active' => true,
        ]);
    }
}
