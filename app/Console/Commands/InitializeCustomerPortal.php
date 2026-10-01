<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerPortalAccountProvisioner;
use Illuminate\Console\Command;

class InitializeCustomerPortal extends Command
{
    protected $signature = 'portal:initialize-customers';
    protected $description = 'Inicializa cuentas de portal elegibles sin reemplazar contraseñas existentes';

    public function handle(CustomerPortalAccountProvisioner $provisioner): int
    {
        $created = 0;
        Customer::where('document_type', 'DNI')->where('status', true)
            ->chunkById(100, function ($customers) use (&$created, $provisioner) {
                foreach ($customers as $customer) {
                    $account = $provisioner->ensureForCustomer($customer);
                    $created += (int) ($account?->wasRecentlyCreated ?? false);
                }
            });
        $this->info("Cuentas creadas: {$created}. Las cuentas existentes permanecen intactas.");
        return self::SUCCESS;
    }
}
