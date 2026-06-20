<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LeoRossi\LaravelImageS3\ImageS3ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanTestingStorage();
    }

    protected function tearDown(): void
    {
        $this->cleanTestingStorage();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ImageS3ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.default', 'testing');
        $app['config']->set('filesystems.disks.testing', [
            'driver' => 'local',
            'root' => storage_path('app/testing'),
            'url' => 'http://localhost/storage/testing',
            'visibility' => 'public',
        ]);

        $app['config']->set('image-s3.disk', 'testing');
        $app['config']->set('image-s3.queue.enabled', false);
        $app['config']->set('image-s3.variants', [
            'small' => [
                'width' => 100,
                'height' => 100,
                'quality' => 80,
                'fit' => 'cover',
            ],
            'medium' => [
                'width' => 500,
                'height' => 400,
                'quality' => 85,
                'fit' => 'contain',
            ],
        ]);
    }

    protected function cleanTestingStorage(): void
    {
        $path = storage_path('app/testing');
        $fs = new Filesystem;

        if ($fs->isDirectory($path)) {
            $fs->deleteDirectory($path);
        }
    }
}
