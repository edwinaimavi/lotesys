<?php

return [
    'attachments_storage' => [
        'driver' => 'local',
        'root' => storage_path('app/private/chat-attachments'),
        'visibility' => 'private',
        'throw' => true,
    ],
];
