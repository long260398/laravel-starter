<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\GalleryAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryDataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_the_module_at_runtime_does_not_delete_existing_data(): void
    {
        $album = GalleryAlbum::factory()->published()->create();

        config(['modules.gallery.enabled' => false]);

        $this->assertDatabaseHas('gallery_albums', ['id' => $album->id]);
        $this->assertNotNull(GalleryAlbum::query()->find($album->id));
    }
}
