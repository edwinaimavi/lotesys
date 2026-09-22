<?php

return [
    'origin_banks' => [
        'Banco de Crédito del Perú - BCP',
        'BBVA Perú',
        'Interbank',
        'Scotiabank Perú',
        'BanBif',
        'Banco Pichincha',
        'Mibanco',
        'Banco de la Nación',
        'Banco de Comercio',
        'Banco Falabella',
        'Otra entidad',
    ],
    // Disco dedicado, fuera de public/ y del enlace storage público.
    'receipts_storage' => [
        'driver' => 'local',
        'root' => storage_path('app/private/payment-receipts'),
        'visibility' => 'private',
        'throw' => true,
    ],
];
