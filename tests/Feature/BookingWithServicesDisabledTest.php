<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * Module Services bị tắt chỉ cho riêng test class này — Booking không phụ
 * thuộc cứng vào Services (đọc qua `module_registry()->bookableSources()`),
 * nên form đặt lịch vẫn phải chạy bình thường, chỉ thiếu dropdown dịch vụ.
 */
class BookingWithServicesDisabledTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['MODULE_SERVICES' => 'false']);

        parent::setUp();
    }

    public function test_booking_create_page_still_renders_without_services_dropdown(): void
    {
        $this->get(route('booking.create'))
            ->assertOk()
            ->assertDontSee('id="bookable_item"', false);
    }

    public function test_booking_submission_still_works_without_services_module(): void
    {
        $response = $this->post(route('booking.store'), [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'a@example.com',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'time_slot' => 'morning',
            'party_size' => 2,
            'note' => '',
            'form_token' => Crypt::encrypt(now()->subSeconds(5)->timestamp),
            'website' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['name' => 'Nguyễn Văn A', 'service_name' => null]);
    }
}
