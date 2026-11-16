<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Services bị tắt chỉ cho riêng test class này. phpunit.xml đặt
 * MODULE_SERVICES=true làm mặc định cho cả suite — ở đây phải set env
 * TRƯỚC khi gọi parent::setUp() để bootstrap/app.php đọc đúng giá trị khi
 * boot app riêng cho từng test (xem Illuminate\Foundation\Testing\TestCase::
 * createApplication(), gọi bootstrap/app.php mới mỗi lần).
 */
class ServiceModuleDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('MODULE_SERVICES=false');
        $_ENV['MODULE_SERVICES'] = 'false';
        $_SERVER['MODULE_SERVICES'] = 'false';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('MODULE_SERVICES=true');
        $_ENV['MODULE_SERVICES'] = 'true';
        $_SERVER['MODULE_SERVICES'] = 'true';

        parent::tearDown();
    }

    public function test_services_route_is_404_when_module_disabled(): void
    {
        $this->get('/services')->assertStatus(404);
    }

    public function test_admin_services_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/services')->assertStatus(404);
    }

    public function test_admin_service_categories_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/service-categories')->assertStatus(404);
    }

    public function test_home_page_has_no_services_section_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Dịch vụ nổi bật');
    }

    public function test_header_has_no_services_nav_link_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Dịch vụ');
    }

    public function test_sitemap_has_no_service_urls_when_module_disabled(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/services/', false);
    }
}
