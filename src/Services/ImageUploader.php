<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Services;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;
use LeoRossi\LaravelImageS3\DTOs\ImageVariantConfig;
use LeoRossi\LaravelImageS3\Exceptions\ImageUploadException;
use LeoRossi\LaravelImageS3\Jobs\ProcessImageVariants;
use LeoRossi\LaravelImageS3\Models\ImageUpload;
use Throwable;

class ImageUploader implements ImageUploaderInterface
{
    protected Repository $config;

    public function __construct(?Repository $config = null)
    {
        $this->config = $config ?? new Repository(config('image-s3', []));
    }

    public function upload(UploadedFile $file, ?string $altText = null): ImageUpload
    {
        if (! $file->isValid()) {
            throw ImageUploadException::invalidImage('The uploaded file is not valid.');
        }

        $disk = $this->disk();
        $diskName = $this->config->get('disk', 's3');
        $filename = $this->makeFilename($file);
        $originalDirectory = rtrim((string) $this->config->get('original_directory', 'original'), '/');
        $originalPath = $originalDirectory.'/'.$filename;

        Log::debug('Uploading original image to disk.', [
            'disk' => $diskName,
            'original_path' => $originalPath,
            'driver' => config("filesystems.disks.{$diskName}.driver", 'unknown'),
            'region' => config("filesystems.disks.{$diskName}.region"),
            'bucket' => config("filesystems.disks.{$diskName}.bucket"),
            'endpoint' => config("filesystems.disks.{$diskName}.endpoint"),
        ]);

        try {
            $disk->putFileAs($originalDirectory, $file, $filename, ['visibility' => 'public']);
        } catch (Throwable $e) {
            Log::error('Failed to store original image on disk.', [
                'disk' => $diskName,
                'original_path' => $originalPath,
                'driver' => config("filesystems.disks.{$diskName}.driver", 'unknown'),
                'region' => config("filesystems.disks.{$diskName}.region"),
                'bucket' => config("filesystems.disks.{$diskName}.bucket"),
                'endpoint' => config("filesystems.disks.{$diskName}.endpoint"),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $upload = new ImageUpload([
            'disk' => $this->config->get('disk', 's3'),
            'filename' => $filename,
            'original_path' => $originalPath,
            'original_url' => $disk->url($originalPath),
            'variants' => [],
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $altText,
        ]);
        $upload->save();

        if ($this->queueEnabled()) {
            ProcessImageVariants::dispatch($upload)
                ->onConnection($this->config->get('queue.connection'))
                ->onQueue($this->config->get('queue.queue'));
        } else {
            $this->processVariantsFor($upload);
        }

        return $upload->fresh();
    }

    public function processVariantsFor(ImageUpload $imageUpload): ImageUpload
    {
        $disk = $this->disk($imageUpload->disk);

        Log::debug('Checking original image existence before generating variants.', [
            'disk' => $imageUpload->disk,
            'original_path' => $imageUpload->original_path,
        ]);

        try {
            $originalExists = $disk->exists($imageUpload->original_path);
        } catch (Throwable $e) {
            Log::error('Unable to check original image existence on disk.', [
                'disk' => $imageUpload->disk,
                'original_path' => $imageUpload->original_path,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        if (! $originalExists) {
            Log::error('Original image does not exist on disk.', [
                'disk' => $imageUpload->disk,
                'original_path' => $imageUpload->original_path,
            ]);

            throw ImageUploadException::missingOriginal($imageUpload);
        }

        $originalBinary = $disk->get($imageUpload->original_path);
        $extension = pathinfo($imageUpload->filename, PATHINFO_EXTENSION);
        $variantsDirectory = rtrim((string) $this->config->get('variants_directory', 'variants'), '/');

        $variants = [];

        foreach ($this->variantConfigs() as $variant) {
            $variantPath = sprintf(
                '%s/%s/%s',
                $variantsDirectory,
                $variant->name,
                $imageUpload->filename
            );

            $processed = $this->resize($originalBinary, $variant, $extension);

            $disk->put($variantPath, $processed, ['visibility' => 'public']);

            $variants[$variant->name] = [
                'path' => $variantPath,
                'url' => $disk->url($variantPath),
                'width' => $variant->width,
                'height' => $variant->height,
                'quality' => $variant->quality,
                'fit' => $variant->fit,
            ];
        }

        $imageUpload->variants = $variants;
        $imageUpload->save();

        return $imageUpload->fresh();
    }

    public function delete(ImageUpload $imageUpload): bool
    {
        $disk = $this->disk($imageUpload->disk);

        $paths = [$imageUpload->original_path];

        foreach ($imageUpload->variants ?? [] as $variant) {
            if (is_array($variant) && isset($variant['path'])) {
                $paths[] = $variant['path'];
            }
        }

        Log::debug('Deleting images from disk.', [
            'disk' => $imageUpload->disk,
            'paths' => $paths,
        ]);

        try {
            $disk->delete($paths);
        } catch (Throwable $e) {
            Log::error('Failed to delete images from disk.', [
                'disk' => $imageUpload->disk,
                'paths' => $paths,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return true;
    }

    public function srcset(ImageUpload $imageUpload): string
    {
        $parts = [];

        foreach ($imageUpload->variants ?? [] as $name => $variant) {
            if (! is_array($variant) || ! isset($variant['url'], $variant['width'])) {
                continue;
            }

            $parts[] = sprintf('%s %dw', $variant['url'], $variant['width']);
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<ImageVariantConfig>
     */
    protected function variantConfigs(): array
    {
        $variants = $this->config->get('variants', []);
        $configs = [];

        foreach ($variants as $name => $config) {
            $configs[] = ImageVariantConfig::fromArray($name, is_array($config) ? $config : []);
        }

        return $configs;
    }

    protected function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($disk ?? $this->config->get('disk', 's3'));
    }

    protected function makeFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');

        return sprintf('%s.%s', (string) Str::uuid(), $extension);
    }

    protected function queueEnabled(): bool
    {
        return (bool) $this->config->get('queue.enabled', false);
    }

    protected function resize(string $binary, ImageVariantConfig $variant, string $extension): string
    {
        $manager = ImageManager::gd();
        $image = $manager->read($binary);

        if ($variant->fit === 'contain') {
            $image = $image->scaleDown($variant->width, $variant->height);
        } else {
            $image = $image->cover($variant->width, $variant->height);
        }

        $encoded = $image->encodeByExtension($extension, quality: $variant->quality);

        return $encoded->toString();
    }
}
