<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_without_its_own_meta_falls_back_to_settings_defaults(): void
    {
        Setting::set('seo_title', 'Mặc định SEO');
        Setting::set('seo_description', 'Mô tả mặc định từ Cài đặt website.');

        $response = $this->get('/');

        $response->assertSee('<title>Mặc định SEO</title>', false);
        $response->assertSee('Mô tả mặc định từ Cài đặt website.', false);
    }

    public function test_static_page_with_its_own_meta_uses_its_own_values(): void
    {
        Setting::set('seo_description', 'Mô tả mặc định.');

        $page = StaticPage::factory()->published()->create([
            'meta_title' => 'Tiêu đề riêng của trang',
            'meta_description' => 'Mô tả riêng của trang.',
        ]);

        $response = $this->get("/{$page->slug}");

        $response->assertSee('<title>Tiêu đề riêng của trang</title>', false);
        $response->assertSee('Mô tả riêng của trang.', false);
        $response->assertDontSee('Mô tả mặc định.', false);
    }
}
