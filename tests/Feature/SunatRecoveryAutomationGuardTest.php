<?php

namespace Tests\Feature;

use Tests\TestCase;

class SunatRecoveryAutomationGuardTest extends TestCase
{
    public function test_real_recovery_command_is_blocked_when_automation_switch_is_disabled(): void
    {
        config()->set('sunat.recovery_automation_enabled', false);

        $this->artisan('invoices:recover-pending-sunat')
            ->expectsOutputToContain('Recuperación automática SUNAT desactivada')
            ->assertSuccessful();
    }
}
