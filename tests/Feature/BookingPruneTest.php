<?php

namespace Tests\Feature;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_old_bookings_by_preferred_date_regardless_of_status(): void
    {
        $months = (int) config('booking.prune_after_months');

        $oldConfirmed = Booking::factory()->confirmed()->create([
            'preferred_date' => now()->subMonths($months + 1)->toDateString(),
        ]);

        $recentCompleted = Booking::factory()->completed()->create([
            'preferred_date' => now()->subMonths($months - 1)->toDateString(),
        ]);

        $upcomingNew = Booking::factory()->create([
            'preferred_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('model:prune', ['--model' => [Booking::class]])->assertExitCode(0);

        $this->assertDatabaseMissing('bookings', ['id' => $oldConfirmed->id]);
        $this->assertDatabaseHas('bookings', ['id' => $recentCompleted->id]);
        $this->assertDatabaseHas('bookings', ['id' => $upcomingNew->id]);
    }

    public function test_prune_deletes_old_booking_even_when_status_is_still_new(): void
    {
        $months = (int) config('booking.prune_after_months');

        $oldNew = Booking::factory()->create([
            'status' => BookingStatus::New,
            'preferred_date' => now()->subMonths($months + 1)->toDateString(),
        ]);

        $this->artisan('model:prune', ['--model' => [Booking::class]]);

        $this->assertDatabaseMissing('bookings', ['id' => $oldNew->id]);
    }
}
