<?php

namespace Tests\Feature;

use App\Modules\Faq\Models\Faq;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Bug đã sửa: ThemeViewResolver::applyModuleOverrides() build đường dẫn
 * override thiếu đoạn "/views" (`{theme}/modules/{key}` thay vì
 * `{theme}/views/modules/{key}`), nên `is_dir()` luôn fail, `replaceNamespace()`
 * không bao giờ chèn được thư mục override của theme — view module gốc luôn
 * được render dù theme có file override thật. ThemeContractTest không bắt
 * được vì chỉ assert render không ném exception, không assert ĐÚNG file nào
 * được dùng.
 *
 * Test này render qua HTTP thật (không gọi view() trực tiếp) với
 * APP_THEME=spa-natural, assert có mặt 1 "data-theme-view" marker chỉ tồn
 * tại trong file override của theme — nếu resolver quay lại render view gốc
 * của module, assertSee sẽ fail.
 */
class ThemeRuntimeModuleOverrideTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        // APP_THEME ảnh hưởng ActiveTheme (singleton, resolve lúc boot) nên
        // phải set TRƯỚC parent::setUp() — xem Tests\Concerns\WithTemporaryEnv.
        $this->setTemporaryEnv(['APP_THEME' => 'spa-natural']);

        parent::setUp();
    }

    public function test_home_page_renders_theme_override_for_services_home_section(): void
    {
        $category = ServiceCategory::factory()->published()->create();
        Service::factory()->published()->featured()->for($category, 'category')->create();

        $this->get('/')->assertSee('data-theme-view="spa-natural:services.home-section"', false);
    }

    public function test_home_page_renders_theme_override_for_gallery_home_section(): void
    {
        GalleryAlbum::factory()->published()->create();

        $this->get('/')->assertSee('data-theme-view="spa-natural:gallery.home-section"', false);
    }

    public function test_home_page_renders_theme_override_for_testimonials_home_section(): void
    {
        Testimonial::factory()->published()->create();

        $this->get('/')->assertSee('data-theme-view="spa-natural:testimonials.home-section"', false);
    }

    public function test_faq_page_renders_theme_override(): void
    {
        Faq::factory()->published()->create();

        $this->get('/faq')->assertSee('data-theme-view="spa-natural:faq.index"', false);
    }
}
