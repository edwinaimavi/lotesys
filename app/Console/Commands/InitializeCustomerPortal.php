<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerPortalAccount;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class InitializeCustomerPortal extends Command
{
    protected $signature = 'portal:initialize-customers';
    protected $description = 'Inicializa cuentas de portal elegibles sin reemplazar contraseñas existentes';

    public function handle(): int
    {
        $created = 0;
        Customer::where('document_type', 'DNI')->where('status', true)
            ->whereIn('id', Sale::select('customer_id')->whereIn('status', ['activo', 'finalizado']))
            ->chunkById(100, function ($customers) use (&$created) {
                foreach ($customers as $customer) {
                    $dni = trim($customer->document_number ?? '');
                    if (! preg_match('/^[0-9]{8}$/D', $dni) || CustomerPortalAccount::customersForDni($dni)->count() !== 1) {
                        continue;
                    }
                    $account = CustomerPortalAccount::firstOrCreate(['customer_id' => $customer->id], [
                        'password' => Hash::make($dni), 'must_change_password' => true, 'is_active' => true,
                    ]);
                    $created += (int) $account->wasRecentlyCreated;
                }
            });
        $this->info("Cuentas creadas: {$created}. Las cuentas existentes permanecen intactas.");
        return self::SUCCESS;
    }
}
