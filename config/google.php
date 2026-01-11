<?php

declare(strict_types=1);

return [
    'project_id' => env('GOOGLE_CLOUD_PROJECT'),
    'storage' => [
        'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
    ],
];
