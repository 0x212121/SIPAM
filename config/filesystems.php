<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Private disk for original evidence photos - NO public access
        'evidence_originals' => [
            'driver' => 'local',
            'root' => storage_path('app/evidence_originals'),
            'visibility' => 'private',
            'throw' => true,
        ],

        // Private disk for preview and thumbnail derivatives - NO public access
        'evidence_derivatives' => [
            'driver' => 'local',
            'root' => storage_path('app/evidence_derivatives'),
            'visibility' => 'private',
            'throw' => true,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
