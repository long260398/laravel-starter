<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_album_cover_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('gallery/covers', 'public');
        $album = GalleryAlbum::factory()->create(['cover_image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('gallery/covers', 'public');
        $album->update(['cover_image' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_soft_deleting_an_album_keeps_cover_image_and_images(): void
    {
        Storage::fake('public');

        $coverPath = UploadedFile::fake()->image('cover.jpg')->store('gallery/covers', 'public');
        $album = GalleryAlbum::factory()->create(['cover_image' => $coverPath]);

        $imagePath = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', 'public');
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePath]);

        $album->delete();

        Storage::disk('public')->assertExists($coverPath);
        Storage::disk('public')->assertExists($imagePath);
        $this->assertDatabaseHas('gallery_images', ['id' => $image->id]);
    }

    public function test_force_deleting_an_album_deletes_cover_image_and_all_images_with_their_files(): void
    {
        Storage::fake('public');

        $coverPath = UploadedFile::fake()->image('cover.jpg')->store('gallery/covers', 'public');
        $album = GalleryAlbum::factory()->create(['cover_image' => $coverPath]);

        $imagePathA = UploadedFile::fake()->image('a.jpg')->store('gallery/images', 'public');
        $imagePathB = UploadedFile::fake()->image('b.jpg')->store('gallery/images', 'public');
        $imageA = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePathA]);
        $imageB = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $imagePathB]);

        $album->forceDelete();

        Storage::disk('public')->assertMissing($coverPath);
        Storage::disk('public')->assertMissing($imagePathA);
        Storage::disk('public')->assertMissing($imagePathB);
        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $imageA->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $imageB->id]);
    }

    /**
     * GalleryImage không có SoftDeletes — delete() là xóa vĩnh viễn duy nhất,
     * nên DeletesStoredFiles phải dọn file ngay ở event `deleted` (không có
     * forceDelete() để mà hook vào). Đây là test cho nhánh mới của trait.
     */
    public function test_deleting_an_image_without_soft_deletes_removes_its_file_immediately(): void
    {
        Storage::fake('public');

        $album = GalleryAlbum::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', 'public');
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $path]);

        $image->delete();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
    }

    public function test_updating_an_image_file_deletes_the_old_one(): void
    {
        Storage::fake('public');

        $album = GalleryAlbum::factory()->create();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('gallery/images', 'public');
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('gallery/images', 'public');
        $image->update(['image' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }
}
