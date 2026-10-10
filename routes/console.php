<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;



Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('latefees:calculate')
    ->daily();

// Recuperación segura de comprobantes SUNAT pendientes por fallas transitorias.
// Se registra únicamente cuando el interruptor está habilitado.
// Cada ejecución consulta /invoice/status antes de reenviar el mismo correlativo.
if (config('sunat.recovery_automation_enabled', false)) {
    Schedule::command('invoices:recover-pending-sunat')
        ->dailyAt('23:00')
        ->withoutOverlapping(30);

    Schedule::command('invoices:recover-pending-sunat')
        ->dailyAt('03:00')
        ->withoutOverlapping(30);
}
