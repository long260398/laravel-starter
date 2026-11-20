<?php

namespace Tests\Unit;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\Setting;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Xác nhận cơ chế mặc định của TracksMediaUsage (dò resource Filament qua
 * Filament::getModelResource()) hoạt động đúng cho model có trang sửa riêng
 * (Post), và các model tự override (GalleryImage, Setting) hiển thị đúng
 * — không cần sửa gì ở App\Models\MediaUsage.
 */
class MediaUsageLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_label_and_url_use_the_registered_filament_resource(): void
    {
        $post = Post::factory()->create(['title' => 'Bài viết mẫu']);

        $this->assertSame('Bài viết: Bài viết mẫu', $post->mediaUsageLabel());
        $this->assertSame(PostResource::getUrl('edit', ['record' => $post]), $post->mediaUsageUrl());
    }

    public function test_gallery_image_points_to_its_parent_album_edit_page(): void
    {
        $album = GalleryAlbum::factory()->create(['title' => 'Album mẫu']);
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id]);

        $this->assertSame('Ảnh trong album: Album mẫu', $image->mediaUsageLabel());
        $this->assertSame(
            GalleryAlbumResource::getUrl('edit', ['record' => $album->id]),
            $image->mediaUsageUrl(),
        );
    }

    public function test_setting_shows_the_key_and_links_to_the_settings_page(): void
    {
        $setting = Setting::query()->create(['key' => 'logo', 'value' => 'settings/logo.png']);

        $this->assertSame('Cài đặt → logo', $setting->mediaUsageLabel());
        $this->assertNotNull($setting->mediaUsageUrl());
    }
}
