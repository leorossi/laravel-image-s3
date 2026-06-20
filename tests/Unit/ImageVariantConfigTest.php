<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Tests\Unit;

use InvalidArgumentException;
use LeoRossi\LaravelImageS3\DTOs\ImageVariantConfig;
use LeoRossi\LaravelImageS3\Tests\TestCase;

class ImageVariantConfigTest extends TestCase
{
    public function test_it_can_be_created_from_config_array(): void
    {
        $config = ImageVariantConfig::fromArray('hero', [
            'width' => 1200,
            'height' => 600,
            'quality' => 90,
            'fit' => 'contain',
        ]);

        $this->assertSame('hero', $config->name);
        $this->assertSame(1200, $config->width);
        $this->assertSame(600, $config->height);
        $this->assertSame(90, $config->quality);
        $this->assertSame('contain', $config->fit);
    }

    public function test_it_defaults_to_cover_fit_and_quality(): void
    {
        $config = ImageVariantConfig::fromArray('thumb', [
            'width' => 100,
            'height' => 100,
        ]);

        $this->assertSame('cover', $config->fit);
        $this->assertSame(90, $config->quality);
    }

    public function test_invalid_dimensions_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageVariantConfig('bad', 0, 100, 80);
    }

    public function test_invalid_quality_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageVariantConfig('bad', 100, 100, 101);
    }

    public function test_invalid_fit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageVariantConfig('bad', 100, 100, 80, 'stretch');
    }
}
