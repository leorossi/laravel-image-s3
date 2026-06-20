<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk used to store images. You can use any disk defined in your
    | Laravel filesystems config. S3, Cloudflare R2 and local are supported.
    |
    */
    'disk' => env('IMAGE_S3_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Directories
    |--------------------------------------------------------------------------
    |
    | The original uploads are stored under "original_directory".
    | Generated variants are stored under "variants_directory/{variant}".
    |
    */
    'original_directory' => 'original',
    'variants_directory' => 'variants',

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | When enabled, image variants are generated asynchronously via Laravel's
    | queue system. When disabled, variants are generated synchronously during
    | the upload request.
    |
    */
    'queue' => [
        'enabled' => env('IMAGE_S3_QUEUE_ENABLED', false),
        'connection' => env('IMAGE_S3_QUEUE_CONNECTION', null),
        'queue' => env('IMAGE_S3_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Variants
    |--------------------------------------------------------------------------
    |
    | Define the sizes/qualities you want generated for every upload.
    | Each variant needs a unique name and may specify:
    |
    | - width: desired width in pixels
    | - height: desired height in pixels
    | - quality: JPEG/WebP quality (0-100)
    | - fit: optional, "cover" (crop to exact size) or "contain" (fit inside)
    |
    */
    'variants' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'quality' => 80,
            'fit' => 'cover',
        ],
        'medium' => [
            'width' => 800,
            'height' => 600,
            'quality' => 85,
            'fit' => 'contain',
        ],
        'large' => [
            'width' => 1920,
            'height' => 1080,
            'quality' => 90,
            'fit' => 'contain',
        ],
    ],
];
