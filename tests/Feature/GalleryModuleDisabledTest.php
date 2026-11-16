<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Gallery bị tắt chỉ cho riêng test class này. phpunit.xml đặt
 * MODULE_GALLERY=true làm mặc định cho cả suite — ở đây phải set env
 * TRƯỚC khi gọi parent::setUp() để bootstrap/app.php đọc đúng giá trị khi
 * boot app riêng cho từng test (xem Illuminate\Foundation\Testing\TestCase::
 * createApplication(), gọi bootstrap/app.php mới mỗi lần).
 */
class GalleryModuleDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('MODULE_GALLERY=false');
        $_ENV['MODULE_GALLERY'] = 'false';
        $_SERVER['MODULE_GALLERY'] = 'false';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('MODULE_GALLERY=true');
        $_ENV['MODULE_GALLERY'] = 'true';
        $_SERVER['MODULE_GALLERY'] = 'true';

        parent::tearDown();
    }

    public function test_gallery_route_is_404_when_module_disabled(): void
    {
        $this->get('/gallery')->assertStatus(404);
    }

    public function test_admin_gallery_albums_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/gallery-albums')->assertStatus(404);
    }

    public function test_home_page_has_no_gallery_section_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Thư viện ảnh');
    }

    public function test_header_has_no_gallery_nav_link_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Thư viện ảnh');
    }

    public function test_sitemap_has_no_gallery_urls_when_module_disabled(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/gallery/', false);
    }
}
