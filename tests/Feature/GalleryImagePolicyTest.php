<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GalleryImage không có SoftDeletes (xem model) nên không có restore/forceDelete
 * — editor có toàn quyền CRUD như admin, không có giới hạn nào để kiểm tra riêng.
 */
class GalleryImagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_has_full_crud_on_images(): void
    {
        $editor = User::factory()->editor()->create();
        $album = GalleryAlbum::factory()->create();
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id]);

        $this->assertTrue($editor->can('viewAny', GalleryImage::class));
        $this->assertTrue($editor->can('create', GalleryImage::class));
        $this->assertTrue($editor->can('update', $image));
        $this->assertTrue($editor->can('delete', $image));
    }

    public function test_admin_has_full_crud_on_images(): void
    {
        $admin = User::factory()->admin()->create();
        $album = GalleryAlbum::factory()->create();
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id]);

        $this->assertTrue($admin->can('delete', $image));
    }
}
