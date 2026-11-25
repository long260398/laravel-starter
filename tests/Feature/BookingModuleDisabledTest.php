<?php

namespace Tests\Feature;

use App\Modules\Services\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Module Booking bị tắt chỉ cho riêng test class này (xem
 * ServiceModuleDisabledTest về lý do set env trước parent::setUp()).
 */
class BookingModuleDisabledTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['MODULE_BOOKING' => 'false']);

        parent::setUp();
    }

    public function test_booking_route_is_404_when_module_disabled(): void
    {
        $this->get('/booking')->assertStatus(404);
    }

    public function test_admin_bookings_route_is_404_when_module_disabled(): void
    {
        $this->get('/admin/bookings')->assertStatus(404);
    }

    public function test_header_has_no_booking_nav_link_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('Đặt lịch');
    }

    public function test_sitemap_has_no_booking_url_when_module_disabled(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/booking', false);
    }

    public function test_service_show_page_has_no_booking_cta_when_module_disabled(): void
    {
        $service = Service::factory()->published()->create();

        $this->get(route('services.show', $service->slug))
            ->assertDontSee('Đặt lịch dịch vụ này');
    }

    public function test_floating_widget_has_no_booking_button_when_module_disabled(): void
    {
        $this->get('/')->assertDontSee('aria-label="Đặt lịch"', false);
    }
}
