<?php

namespace Tests\Feature;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giả lập một module hoàn toàn mới chỉ bằng cách gọi vào ModuleRegistry trực
 * tiếp (như một module thật sẽ làm trong ServiceProvider::boot() của nó) —
 * không tạo provider/route/config nào. Chứng minh core (header, home,
 * sitemap) hiển thị được nội dung này mà không cần biết tên module.
 */
class ModuleRegistryFakeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('fake', base_path('tests/Fixtures/views'));
    }

    public function test_home_page_renders_registered_fake_home_section(): void
    {
        module_registry()->registerHomeSection('fake::fake-home-section', [
            'heading' => 'Khối giả lập của module khác',
        ]);

        $this->get('/')->assertSee('Khối giả lập của module khác');
    }

    public function test_header_renders_registered_fake_nav_item(): void
    {
        module_registry()->registerNavItem('fake::fake-nav-item', [
            'url' => '/fake-module',
            'label' => 'Menu giả lập',
        ]);

        $this->get('/')->assertSee('Menu giả lập')->assertSee('/fake-module', false);
    }

    public function test_sitemap_includes_urls_from_registered_fake_provider(): void
    {
        module_registry()->registerSitemapUrls(fn (): array => [
            ['loc' => url('/fake-module/trang-gia'), 'lastmod' => now()],
        ]);

        $this->get('/sitemap.xml')->assertSee(url('/fake-module/trang-gia'), false);
    }

    public function test_home_section_data_closure_is_resolved_lazily_at_render_time(): void
    {
        $queried = false;

        module_registry()->registerHomeSection('fake::fake-home-section', function () use (&$queried): array {
            $queried = true;

            return ['heading' => 'Đã resolve lazy'];
        });

        $this->assertFalse($queried);

        $this->get('/')->assertSee('Đã resolve lazy');

        $this->assertTrue($queried);
    }

    public function test_registry_is_a_singleton_across_the_request(): void
    {
        $this->assertSame(app(ModuleRegistry::class), module_registry());
    }
}
