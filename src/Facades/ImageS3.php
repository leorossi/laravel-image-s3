<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Facades;

use Illuminate\Support\Facades\Facade;
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;

/**
 * @method static \LeoRossi\LaravelImageS3\Models\ImageUpload upload(\Illuminate\Http\UploadedFile $file, ?string $altText = null)
 * @method static \LeoRossi\LaravelImageS3\Models\ImageUpload processVariantsFor(\LeoRossi\LaravelImageS3\Models\ImageUpload $imageUpload)
 * @method static bool delete(\LeoRossi\LaravelImageS3\Models\ImageUpload $imageUpload)
 * @method static string srcset(\LeoRossi\LaravelImageS3\Models\ImageUpload $imageUpload)
 */
class ImageS3 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ImageUploaderInterface::class;
    }
}
