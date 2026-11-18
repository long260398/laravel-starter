<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Module Faq bị tắt chỉ cho riêng test class này. phpunit.xml đặt
 * MODULE_FAQ=true làm mặc định cho cả suite — ở đây phải set env TRƯỚC khi
 * gọi parent::setUp() (xem ServiceModuleDisabledTest).
 */
class FaqModuleDisabledTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['MODULE_FAQ' => 'false']);

        parent::setUp();
    }

    public function test_faq_route_is_404_when_module_disabled(): void
    {
        $this->get('/faq')->assertStatus(404);
    }

    public function test_admin_faqs_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/faqs')->assertStatus(404);
    }

    public function test_header_has_no_faq_nav_link_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Hỏi đáp');
    }

    public function test_sitemap_has_no_faq_url_when_module_disabled(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/faq', false);
    }
}
