<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Contracts;

use Illuminate\Http\UploadedFile;
use LeoRossi\LaravelImageS3\Models\ImageUpload;

interface ImageUploaderInterface
{
    /**
     * Upload an image, store the original and generate variants.
     */
    public function upload(UploadedFile $file, ?string $altText = null): ImageUpload;

    /**
     * Generate variants for an existing upload.
     */
    public function processVariantsFor(ImageUpload $imageUpload): ImageUpload;

    /**
     * Delete the original and all variants from storage.
     */
    public function delete(ImageUpload $imageUpload): bool;

    /**
     * Build a srcset string from the upload's variants.
     */
    public function srcset(ImageUpload $imageUpload): string;
}
