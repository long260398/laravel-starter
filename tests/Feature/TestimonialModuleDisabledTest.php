<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Module Testimonials bị tắt chỉ cho riêng test class này. phpunit.xml đặt
 * MODULE_TESTIMONIALS=true làm mặc định cho cả suite — ở đây phải set env
 * TRƯỚC khi gọi parent::setUp() (xem ServiceModuleDisabledTest).
 */
class TestimonialModuleDisabledTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['MODULE_TESTIMONIALS' => 'false']);

        parent::setUp();
    }

    public function test_admin_testimonials_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/testimonials')->assertStatus(404);
    }

    public function test_home_page_has_no_testimonials_section_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Khách hàng nói gì');
    }
}
