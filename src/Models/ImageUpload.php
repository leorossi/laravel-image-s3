<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LeoRossi\LaravelImageS3\Facades\ImageS3;

class ImageUpload extends Model
{
    protected $fillable = [
        'disk',
        'filename',
        'original_path',
        'original_url',
        'variants',
        'mime_type',
        'size',
        'alt_text',
    ];

    protected $casts = [
        'variants' => 'array',
        'size' => 'integer',
    ];

    /**
     * Delete the image record and all associated files.
     */
    public function delete(): ?bool
    {
        ImageS3::delete($this);

        return parent::delete();
    }

    /**
     * Get the URL for a specific variant.
     */
    public function variantUrl(string $name): ?string
    {
        return $this->variants[$name]['url'] ?? null;
    }

    /**
     * Get the path for a specific variant.
     */
    public function variantPath(string $name): ?string
    {
        return $this->variants[$name]['path'] ?? null;
    }

    /**
     * Generate a srcset string from the configured variants.
     */
    public function srcset(): string
    {
        return ImageS3::srcset($this);
    }

    /**
     * Determine if variants have already been generated.
     */
    public function hasVariants(): bool
    {
        return ! empty($this->variants);
    }
}
