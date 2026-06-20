<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3;

use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;
use LeoRossi\LaravelImageS3\Services\ImageUploader;

class ImageS3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/image-s3.php', 'image-s3');

        $this->app->singleton(ImageUploaderInterface::class, function ($app) {
            return new ImageUploader(
                new Repository($app['config']->get('image-s3', []))
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/image-s3.php' => config_path('image-s3.php'),
            ], 'image-s3-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'image-s3-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
