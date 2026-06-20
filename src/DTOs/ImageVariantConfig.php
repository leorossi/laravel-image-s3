<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\DTOs;

use InvalidArgumentException;

final readonly class ImageVariantConfig
{
    public function __construct(
        public string $name,
        public int $width,
        public int $height,
        public int $quality,
        public string $fit = 'cover',
    ) {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Variant width and height must be at least 1.');
        }

        if ($quality < 0 || $quality > 100) {
            throw new InvalidArgumentException('Variant quality must be between 0 and 100.');
        }

        if (! in_array($fit, ['cover', 'contain'], true)) {
            throw new InvalidArgumentException('Variant fit must be "cover" or "contain".');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(string $name, array $config): self
    {
        return new self(
            name: $name,
            width: (int) ($config['width'] ?? 0),
            height: (int) ($config['height'] ?? 0),
            quality: (int) ($config['quality'] ?? 90),
            fit: (string) ($config['fit'] ?? 'cover'),
        );
    }
}
