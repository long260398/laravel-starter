<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryAlbumCoverUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_url_uses_the_first_image_by_sort_order_when_available(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create(['cover_image' => null]);
        $second = UploadedFile::fake()->image('second.jpg')->store('gallery/images', config('media.disk'));
        $first = UploadedFile::fake()->image('first.jpg')->store('gallery/images', config('media.disk'));
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $second, 'sort_order' => 2]);
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $first, 'sort_order' => 1]);

        $this->assertSame(public_file_url($first), $album->cover_url);
    }

    public function test_cover_url_falls_back_to_legacy_cover_image_when_album_has_no_images(): void
    {
        Storage::fake(config('media.disk'));

        $coverPath = UploadedFile::fake()->image('cover.jpg')->store('gallery/covers', config('media.disk'));
        $album = GalleryAlbum::factory()->create(['cover_image' => $coverPath]);

        $this->assertSame(public_file_url($coverPath), $album->cover_url);
    }

    public function test_cover_url_is_null_when_album_has_neither_cover_image_nor_images(): void
    {
        $album = GalleryAlbum::factory()->create(['cover_image' => null]);

        $this->assertNull($album->cover_url);
    }
}
