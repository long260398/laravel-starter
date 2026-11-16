<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_200_and_lists_published_albums(): void
    {
        $album = GalleryAlbum::factory()->published()->create();

        $this->get('/gallery')->assertStatus(200)->assertSee($album->title);
    }

    public function test_index_does_not_list_draft_albums(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->get('/gallery')->assertDontSee($album->title);
    }

    public function test_show_returns_200_for_published_album_and_lists_its_images(): void
    {
        $album = GalleryAlbum::factory()->published()->create();
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'caption' => 'Ảnh demo']);

        $response = $this->get("/gallery/{$album->slug}");

        $response->assertStatus(200)->assertSee($album->title)->assertSee('Ảnh demo');
    }

    public function test_show_returns_404_for_draft_album(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->get("/gallery/{$album->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/gallery/khong-ton-tai')->assertStatus(404);
    }
}
