<?php

namespace Tests\Feature;

use App\Modules\Gallery\Models\GalleryAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_gallery_section_with_published_albums(): void
    {
        $album = GalleryAlbum::factory()->published()->create();

        $response = $this->get('/');

        $response->assertSee('Thư viện ảnh');
        $response->assertSee($album->title);
    }

    public function test_home_page_hides_section_when_no_published_albums(): void
    {
        GalleryAlbum::factory()->create(); // draft

        $content = $this->get('/')->getContent();

        // "Thư viện ảnh" cũng là nhãn menu, lặp lại ở cả nav desktop và mobile
        // (xem layouts/header.blade.php) — baseline là 2 lần. Không có album
        // xuất bản thì không có thêm lần nào từ section trang chủ.
        $this->assertSame(2, substr_count($content, 'Thư viện ảnh'));
    }

    public function test_header_has_gallery_nav_link(): void
    {
        $this->get('/')->assertSee('Thư viện ảnh')->assertSee(route('gallery.index'), false);
    }

    public function test_sitemap_includes_published_album(): void
    {
        $album = GalleryAlbum::factory()->published()->create();

        $this->get('/sitemap.xml')->assertSee(route('gallery.show', $album->slug), false);
    }

    public function test_sitemap_excludes_draft_album(): void
    {
        $album = GalleryAlbum::factory()->create();

        $this->get('/sitemap.xml')->assertDontSee(route('gallery.show', $album->slug), false);
    }
}
