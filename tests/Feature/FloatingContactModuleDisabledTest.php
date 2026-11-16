<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Module FloatingContact bị tắt chỉ cho riêng test class này. phpunit.xml đặt
 * MODULE_FLOATING_CONTACT=true làm mặc định cho cả suite — ở đây phải set env
 * TRƯỚC khi gọi parent::setUp() để bootstrap/app.php đọc đúng giá trị (xem
 * ServiceModuleDisabledTest).
 */
class FloatingContactModuleDisabledTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['MODULE_FLOATING_CONTACT' => 'false']);

        parent::setUp();
    }

    public function test_admin_settings_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/floating-contact-settings')->assertStatus(404);
    }

    public function test_home_page_has_no_floating_buttons_when_module_disabled(): void
    {
        Setting::set('floating_contact.enable_call', true);
        Setting::set('hotline', '0909123456');

        $this->get('/')->assertDontSee('aria-label="Gọi điện"', false);
    }
}
