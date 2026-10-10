<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recuperación automática de comprobantes SUNAT
    |--------------------------------------------------------------------------
    |
    | Por seguridad permanece desactivada por defecto. El comando con
    | --dry-run seguirá disponible para inspeccionar candidatos sin realizar
    | llamadas a APISPERU/SUNAT. En producción debe habilitarse de forma
    | explícita una vez validado el despliegue.
    |
    */
    'recovery_automation_enabled' => env(
        'SUNAT_RECOVERY_AUTOMATION_ENABLED',
        false
    ),
];
