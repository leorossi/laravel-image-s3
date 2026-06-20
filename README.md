# Laravel Image S3

A lightweight Laravel package to upload images to S3-compatible storage (AWS S3, Cloudflare R2, MinIO, DigitalOcean Spaces) or a local disk, and automatically generate configurable size/quality variants.

## Requirements

- PHP `^8.2`
- Laravel `^11.0 | ^12.0 | ^13.0`
- GD extension (used by Intervention Image for processing)

## Installation

```bash
composer require leorossi/laravel-image-s3
```

Publish the config file:

```bash
php artisan vendor:publish --tag=image-s3-config
```

If you want to publish migrations instead of using auto-discovery:

```bash
php artisan vendor:publish --tag=image-s3-migrations
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

The published config file lives at `config/image-s3.php`.

```php
return [
    'disk' => env('IMAGE_S3_DISK', 's3'),

    'original_directory' => 'original',
    'variants_directory' => 'variants',

    'queue' => [
        'enabled' => env('IMAGE_S3_QUEUE_ENABLED', false),
        'connection' => env('IMAGE_S3_QUEUE_CONNECTION', null),
        'queue' => env('IMAGE_S3_QUEUE_NAME', 'default'),
    ],

    'variants' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'quality' => 80,
            'fit' => 'cover',   // crop to exact size
        ],
        'medium' => [
            'width' => 800,
            'height' => 600,
            'quality' => 85,
            'fit' => 'contain', // fit inside dimensions
        ],
        'large' => [
            'width' => 1920,
            'height' => 1080,
            'quality' => 90,
            'fit' => 'contain',
        ],
    ],
];
```

### Adding custom variants

You can add as many variants as you want. Each variant needs:

- `width` — target width in pixels
- `height` — target height in pixels
- `quality` — JPEG/WebP quality (0–100)
- `fit` — `cover` (crop to exact dimensions) or `contain` (fit inside dimensions)

## Usage

### Upload an image

```php
use LeoRossi\LaravelImageS3\Facades\ImageS3;

$upload = ImageS3::upload($request->file('photo'), 'My photo alt text');

$upload->original_url;
$upload->variantUrl('thumbnail');
$upload->srcset();
```

### Using the service directly

```php
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;

public function __construct(private ImageUploaderInterface $uploader) {}

$upload = $this->uploader->upload($request->file('photo'));
```

### Queue variant generation

Set `queue.enabled` to `true` to process variants asynchronously. The original image is stored immediately; a `ProcessImageVariants` job is dispatched to generate variants.

### Delete an upload

Deleting the model also removes the original and all variants from storage:

```php
$upload->delete();
```

Or use the facade/service:

```php
ImageS3::delete($upload);
```

## S3 / Cloudflare R2 Setup

Laravel does not include the S3 Flysystem adapter by default. Install it first:

```bash
composer require league/flysystem-aws-s3-v3
```

Configure your disk as usual in `config/filesystems.php`:

```php
'r2' => [
    'driver' => 's3',
    'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
    'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
    'region' => 'auto',
    'bucket' => env('CLOUDFLARE_R2_BUCKET'),
    'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'visibility' => 'public',
],
```

Then set `IMAGE_S3_DISK=r2` in your `.env`.

## Testing

The package ships with PHPUnit tests using a local `testing` disk:

```bash
composer install
vendor/bin/phpunit
```

## License

MIT
