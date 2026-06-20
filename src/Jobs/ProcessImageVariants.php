<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;
use LeoRossi\LaravelImageS3\Models\ImageUpload;

class ProcessImageVariants implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public ImageUpload $imageUpload)
    {
    }

    public function handle(ImageUploaderInterface $uploader): void
    {
        $uploader->processVariantsFor($this->imageUpload);
    }
}
