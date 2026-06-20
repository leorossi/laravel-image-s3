<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Exceptions;

use Exception;
use LeoRossi\LaravelImageS3\Models\ImageUpload;

class ImageUploadException extends Exception
{
    public static function invalidImage(string $message = 'The provided file is not a valid image.'): self
    {
        return new self($message);
    }

    public static function missingOriginal(ImageUpload $imageUpload): self
    {
        return new self(sprintf('Original file not found on disk for upload [%s].', $imageUpload->id));
    }
}
