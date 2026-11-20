<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Setting;
use App\Modules\Gallery\Actions\SyncGalleryAlbumImages;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_album_cover_image_keeps_the_old_file_but_moves_its_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('gallery/covers', config('media.disk'));
        $album = GalleryAlbum::factory()->create(['cover_image' => $oldPath]);
        $oldMedia = Media::query()->where('path', $oldPath)->first();

        $newPath = UploadedFile::fake()->image('new.jpg')->store('gallery/covers', config('media.disk'));
        $album->update(['cover_image' => $newPath]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertFalse($oldMedia->refresh()->isUsed());
        $this->assertTrue(Media::query()->where('path', $newPath)->first()->isUsed());
    }

    public function test_soft_deleting_an_album_keeps_usage_of_cover_image_and_images(): void
    {
        Storage::fake(config('media.disk'));

        $coverPath = UploadedFile::fake()->image('cover.jpg')->store('gallery/covers', config('media.disk'));
        $album = GalleryAlbum::factory()->create(['cover_image' => $coverPath]);

        $imagePath = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', config('media.disk'));
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePath]);

        $album->delete();

        $this->assertTrue(Media::query()->where('path', $coverPath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $imagePath)->first()->isUsed());
        $this->assertDatabaseHas('gallery_images', ['id' => $image->id]);
    }

    public function test_force_deleting_an_album_releases_usage_of_cover_image_and_all_images_but_keeps_the_files(): void
    {
        Storage::fake(config('media.disk'));

        $coverPath = UploadedFile::fake()->image('cover.jpg')->store('gallery/covers', config('media.disk'));
        $album = GalleryAlbum::factory()->create(['cover_image' => $coverPath]);

        $imagePathA = UploadedFile::fake()->image('a.jpg')->store('gallery/images', config('media.disk'));
        $imagePathB = UploadedFile::fake()->image('b.jpg')->store('gallery/images', config('media.disk'));
        $imageA = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePathA]);
        $imageB = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePathB]);

        $album->forceDelete();

        Storage::disk(config('media.disk'))->assertExists($coverPath);
        Storage::disk(config('media.disk'))->assertExists($imagePathA);
        Storage::disk(config('media.disk'))->assertExists($imagePathB);
        $this->assertFalse(Media::query()->where('path', $coverPath)->first()->isUsed());
        $this->assertFalse(Media::query()->where('path', $imagePathA)->first()->isUsed());
        $this->assertFalse(Media::query()->where('path', $imagePathB)->first()->isUsed());
        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $imageA->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $imageB->id]);
    }

    /**
     * GalleryImage không có SoftDeletes — delete() là xóa vĩnh viễn duy nhất,
     * nên TracksMediaUsage phải gỡ usage ngay ở event `deleted` (không có
     * forceDelete() để mà hook vào). Đây là test cho nhánh mới của trait.
     */
    public function test_deleting_an_image_without_soft_deletes_releases_its_usage_immediately(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', config('media.disk'));
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $path]);

        $image->delete();

        $this->assertFalse(Media::query()->where('path', $path)->first()->isUsed());
        Storage::disk(config('media.disk'))->assertExists($path);
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
    }

    /**
     * Giai đoạn 3: 1 ảnh có thể được dùng ở nhiều nơi khác nhau qua MediaPicker
     * (vd vừa là logo vừa nằm trong 1 album) — mỗi nơi 1 usage riêng, gỡ khỏi
     * 1 nơi không ảnh hưởng usage ở nơi còn lại, file không bị xóa.
     */
    public function test_an_image_shared_between_the_logo_and_an_album_keeps_the_logo_usage_after_removal_from_the_album(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('shared.jpg')->store('gallery/images', config('media.disk'));
        Setting::set('logo', $path);

        $album = GalleryAlbum::factory()->create();
        app(SyncGalleryAlbumImages::class)->handle($album, [$path]);

        $media = Media::query()->where('path', $path)->first();
        $this->assertSame(2, $media->usages()->count());

        app(SyncGalleryAlbumImages::class)->handle($album, []);

        $this->assertSame(0, $album->images()->count());
        $this->assertTrue($media->refresh()->isUsed());
        $this->assertSame($path, Setting::get('logo'));
        Storage::disk(config('media.disk'))->assertExists($path);
    }

    public function test_updating_an_image_file_moves_the_old_file_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('gallery/images', config('media.disk'));
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('gallery/images', config('media.disk'));
        $image->update(['image' => $newPath]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $newPath)->first()->isUsed());
    }
}
