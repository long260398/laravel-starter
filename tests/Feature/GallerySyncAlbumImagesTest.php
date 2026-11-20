<?php

namespace Tests\Feature;

use App\Modules\Gallery\Actions\SyncGalleryAlbumImages;
use App\Modules\Gallery\Exceptions\InvalidGalleryImagePath;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class GallerySyncAlbumImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_images_in_submitted_order(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();

        $paths = [
            UploadedFile::fake()->image('a.jpg')->store('gallery/images', config('media.disk')),
            UploadedFile::fake()->image('b.jpg')->store('gallery/images', config('media.disk')),
            UploadedFile::fake()->image('c.jpg')->store('gallery/images', config('media.disk')),
        ];

        app(SyncGalleryAlbumImages::class)->handle($album, $paths);

        $this->assertSame($paths, $album->images()->orderBy('sort_order')->pluck('image')->all());
    }

    public function test_reordering_updates_sort_order_without_changing_row_count(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $first = UploadedFile::fake()->image('first.jpg')->store('gallery/images', config('media.disk'));
        $second = UploadedFile::fake()->image('second.jpg')->store('gallery/images', config('media.disk'));
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $first, 'sort_order' => 1]);
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $second, 'sort_order' => 2]);

        app(SyncGalleryAlbumImages::class)->handle($album, [$second, $first]);

        $this->assertSame(2, $album->images()->count());
        $this->assertSame([$second, $first], $album->images()->orderBy('sort_order')->pluck('image')->all());
    }

    public function test_removing_an_image_deletes_its_row_but_keeps_the_file(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $kept = UploadedFile::fake()->image('kept.jpg')->store('gallery/images', config('media.disk'));
        $removed = UploadedFile::fake()->image('removed.jpg')->store('gallery/images', config('media.disk'));
        $removedImage = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $removed, 'sort_order' => 1]);
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $kept, 'sort_order' => 2]);

        app(SyncGalleryAlbumImages::class)->handle($album, [$kept]);

        $this->assertDatabaseMissing('gallery_images', ['id' => $removedImage->id]);
        Storage::disk(config('media.disk'))->assertExists($removed);
        Storage::disk(config('media.disk'))->assertExists($kept);
    }

    public function test_adding_a_new_image_appends_it_to_the_end(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $existing = UploadedFile::fake()->image('existing.jpg')->store('gallery/images', config('media.disk'));
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $existing, 'sort_order' => 1]);

        $new = UploadedFile::fake()->image('new.jpg')->store('gallery/images', config('media.disk'));

        app(SyncGalleryAlbumImages::class)->handle($album, [$existing, $new]);

        $this->assertSame([$existing, $new], $album->images()->orderBy('sort_order')->pluck('image')->all());
    }

    public function test_rejects_a_path_outside_the_gallery_images_directory_and_touches_nothing(): void
    {
        Storage::fake(config('media.disk'));

        $otherModuleFile = 'posts/other-module.jpg';
        Storage::disk(config('media.disk'))->put($otherModuleFile, 'content');
        $album = GalleryAlbum::factory()->create();

        $this->expectException(InvalidGalleryImagePath::class);

        try {
            app(SyncGalleryAlbumImages::class)->handle($album, [$otherModuleFile]);
        } finally {
            $this->assertSame(0, $album->images()->count());
            Storage::disk(config('media.disk'))->assertExists($otherModuleFile);
        }
    }

    public function test_rejects_a_path_traversal_attempt(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();

        $this->expectException(InvalidGalleryImagePath::class);

        try {
            app(SyncGalleryAlbumImages::class)->handle($album, ['gallery/images/../../.env']);
        } finally {
            $this->assertSame(0, $album->images()->count());
        }
    }

    public function test_failed_sync_cleans_up_only_the_freshly_uploaded_files(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $existing = UploadedFile::fake()->image('existing.jpg')->store('gallery/images', config('media.disk'));
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $existing, 'sort_order' => 1]);

        $fresh = UploadedFile::fake()->image('fresh.jpg')->store('gallery/images', config('media.disk'));
        $failing = UploadedFile::fake()->image('force-fail.jpg')->store('gallery/images', config('media.disk'));

        // Buộc transaction thất bại giữa chừng (sau khi 1 ảnh mới đã được tạo)
        // bằng 1 listener model event chỉ sống trong test này — không đụng code
        // Action. Không dọn đăng ký ở cuối vì app test mới được tạo lại mỗi test.
        GalleryImage::creating(function (GalleryImage $image) use ($failing): void {
            if ($image->image === $failing) {
                throw new RuntimeException('Lỗi giả lập giữa chừng để test rollback.');
            }
        });

        try {
            app(SyncGalleryAlbumImages::class)->handle($album, [$existing, $fresh, $failing]);
            $this->fail('Kỳ vọng SyncGalleryAlbumImages ném exception.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(1, $album->images()->count());
        Storage::disk(config('media.disk'))->assertExists($existing);
        Storage::disk(config('media.disk'))->assertMissing($fresh);
        Storage::disk(config('media.disk'))->assertMissing($failing);
    }
}
