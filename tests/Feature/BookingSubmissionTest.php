<?php

namespace Tests\Feature;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Services\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'a@example.com',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'time_slot' => 'morning',
            'party_size' => 2,
            'note' => 'Ghi chú test',
        ], $overrides);
    }

    private function freshToken(int $secondsAgo = 5): string
    {
        return Crypt::encrypt(now()->subSeconds($secondsAgo)->timestamp);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return array_merge([
            ...$this->validPayload(),
            'form_token' => $this->freshToken(),
            'website' => '',
        ], $overrides);
    }

    public function test_valid_submission_is_saved(): void
    {
        $response = $this->post(route('booking.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'status' => 'new',
            'time_slot' => 'morning',
            'time_slot_label' => 'Buổi sáng',
        ]);
    }

    public function test_invalid_submission_is_rejected(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['name' => '']));

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_honeypot_filled_is_not_saved_but_returns_success(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['website' => 'http://spam.example']));

        $response->assertRedirect();
        $response->assertSessionHas('booking_success');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_too_fast_submission_is_blocked(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['form_token' => $this->freshToken(0)]));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_tampered_token_is_blocked(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['form_token' => 'khong-hop-le']));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_expired_token_is_blocked(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['form_token' => $this->freshToken(7201)]));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_strict_rate_limit_blocks_the_fourth_successful_submission_within_ten_minutes(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('booking.store'), $this->postData(['phone' => "09123456{$i}0"]))
                ->assertRedirect();
        }

        $this->assertDatabaseCount('bookings', 3);

        $response = $this->post(route('booking.store'), $this->postData(['phone' => '0912345640']));

        $response->assertStatus(429);
        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_failed_validations_do_not_count_toward_strict_rate_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('booking.store'), $this->postData(['name' => '']))
                ->assertSessionHasErrors('name');
        }

        $response = $this->post(route('booking.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_preferred_date_in_the_past_is_rejected(): void
    {
        $response = $this->post(route('booking.store'), $this->postData([
            'preferred_date' => now()->subDay()->toDateString(),
        ]));

        $response->assertSessionHasErrors('preferred_date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_preferred_date_too_far_in_the_future_is_rejected(): void
    {
        $response = $this->post(route('booking.store'), $this->postData([
            'preferred_date' => now()->addDays((int) config('booking.max_advance_days') + 1)->toDateString(),
        ]));

        $response->assertSessionHasErrors('preferred_date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_time_slot_must_be_one_of_configured_slots(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['time_slot' => 'midnight']));

        $response->assertSessionHasErrors('time_slot');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_party_size_out_of_range_is_rejected(): void
    {
        $response = $this->post(route('booking.store'), $this->postData(['party_size' => 11]));

        $response->assertSessionHasErrors('party_size');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_valid_service_prefill_is_saved_with_service_name(): void
    {
        $service = Service::factory()->published()->create(['name' => 'Massage đá nóng']);

        $response = $this->post(route('booking.store'), $this->postData([
            'bookable_item' => "services:{$service->slug}",
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['service_name' => 'Massage đá nóng']);
    }

    public function test_invalid_service_slug_is_ignored(): void
    {
        $response = $this->post(route('booking.store'), $this->postData([
            'bookable_item' => 'services:khong-ton-tai',
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['service_name' => null]);
    }

    public function test_draft_service_slug_is_ignored(): void
    {
        $service = Service::factory()->create();

        $response = $this->post(route('booking.store'), $this->postData([
            'bookable_item' => "services:{$service->slug}",
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', ['service_name' => null]);
    }

    public function test_booking_without_contact_email_setting_still_saves_without_sending_mail(): void
    {
        Mail::shouldReceive('to')->never();
        Log::shouldReceive('warning')->once();

        $response = $this->post(route('booking.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_create_page_prefills_valid_service_from_query_string(): void
    {
        $service = Service::factory()->published()->create(['name' => 'Chăm sóc da mặt']);

        $this->get(route('booking.create', ['source' => 'services', 'item' => $service->slug]))
            ->assertOk()
            ->assertSee('Chăm sóc da mặt');
    }

    public function test_booking_model_casts_status_enum(): void
    {
        $booking = Booking::factory()->create();

        $this->assertInstanceOf(BookingStatus::class, $booking->status);
    }
}
