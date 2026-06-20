<?php

declare(strict_types=1);

namespace LeoRossi\LaravelImageS3\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use LeoRossi\LaravelImageS3\Contracts\ImageUploaderInterface;
use LeoRossi\LaravelImageS3\Facades\ImageS3;
use LeoRossi\LaravelImageS3\Jobs\ProcessImageVariants;
use LeoRossi\LaravelImageS3\Models\ImageUpload;
use LeoRossi\LaravelImageS3\Tests\TestCase;

class ImageUploadTest extends TestCase
{
    protected function createTestImage(int $width = 2000, int $height = 1500, string $name = 'test-image.jpg'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test').'.jpg';
        $image = ImageManager::gd()->create($width, $height)->encodeByExtension('jpg', quality: 90);
        file_put_contents($path, $image->toString());

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public function test_it_uploads_image_and_generates_variants(): void
    {
        $file = $this->createTestImage();

        $upload = ImageS3::upload($file, 'A nice photo');

        $this->assertInstanceOf(ImageUpload::class, $upload);
        $this->assertNotNull($upload->id);
        $this->assertSame('A nice photo', $upload->alt_text);
        $this->assertTrue(Storage::disk('testing')->exists($upload->original_path));
        $this->assertStringContainsString('original/', $upload->original_path);

        $this->assertArrayHasKey('small', $upload->variants);
        $this->assertArrayHasKey('medium', $upload->variants);

        $this->assertTrue(Storage::disk('testing')->exists($upload->variantPath('small')));
        $this->assertTrue(Storage::disk('testing')->exists($upload->variantPath('medium')));
    }

    public function test_variants_have_expected_dimensions(): void
    {
        $file = $this->createTestImage();

        $upload = ImageS3::upload($file);

        $small = $this->readVariant($upload->variantPath('small'));
        $this->assertSame(100, $small->width());
        $this->assertSame(100, $small->height());

        $medium = $this->readVariant($upload->variantPath('medium'));
        $this->assertLessThanOrEqual(500, $medium->width());
        $this->assertLessThanOrEqual(400, $medium->height());
    }

    public function test_it_generates_a_srcset(): void
    {
        $file = $this->createTestImage();

        $upload = ImageS3::upload($file);
        $srcset = ImageS3::srcset($upload);

        $this->assertStringContainsString($upload->variantUrl('small').' 100w', $srcset);
        $this->assertStringContainsString($upload->variantUrl('medium').' 500w', $srcset);
    }

    public function test_it_deletes_original_and_variants(): void
    {
        $file = $this->createTestImage();

        $upload = ImageS3::upload($file);
        $originalPath = $upload->original_path;
        $smallPath = $upload->variantPath('small');

        $this->assertTrue(Storage::disk('testing')->exists($originalPath));

        ImageS3::delete($upload);

        $this->assertFalse(Storage::disk('testing')->exists($originalPath));
        $this->assertFalse(Storage::disk('testing')->exists($smallPath));
    }

    public function test_model_delete_also_removes_files(): void
    {
        $file = $this->createTestImage();
        $upload = ImageS3::upload($file);
        $originalPath = $upload->original_path;

        $upload->delete();

        $this->assertFalse(Storage::disk('testing')->exists($originalPath));
        $this->assertDatabaseMissing('image_uploads', ['id' => $upload->id]);
    }

    public function test_queue_mode_dispatches_job(): void
    {
        Bus::fake();

        config()->set('image-s3.queue.enabled', true);

        $file = $this->createTestImage();
        $upload = ImageS3::upload($file);

        Bus::assertDispatched(ProcessImageVariants::class, function (ProcessImageVariants $job) use ($upload) {
            return $job->imageUpload->is($upload);
        });

        $this->assertEmpty($upload->fresh()->variants);
    }

    public function test_queued_variants_are_generated_when_job_runs(): void
    {
        Bus::fake([ProcessImageVariants::class]);

        config()->set('image-s3.queue.enabled', true);

        $file = $this->createTestImage();
        $upload = ImageS3::upload($file);

        Bus::assertDispatched(ProcessImageVariants::class, function (ProcessImageVariants $job) use ($upload) {
            return $job->imageUpload->is($upload);
        });

        $this->assertEmpty($upload->fresh()->variants);
        $this->assertTrue(Storage::disk('testing')->exists($upload->original_path));

        $job = new ProcessImageVariants($upload);
        $job->handle(app(ImageUploaderInterface::class));

        $upload = $upload->fresh();

        $this->assertArrayHasKey('small', $upload->variants);
        $this->assertArrayHasKey('medium', $upload->variants);
        $this->assertTrue(Storage::disk('testing')->exists($upload->variantPath('small')));
        $this->assertTrue(Storage::disk('testing')->exists($upload->variantPath('medium')));
    }

    protected function readVariant(string $path): \Intervention\Image\Image
    {
        $binary = Storage::disk('testing')->get($path);

        return ImageManager::gd()->read($binary);
    }
}
