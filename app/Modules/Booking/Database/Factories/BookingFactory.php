<?php

namespace App\Modules\Booking\Database\Factories;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $timeSlots = config('booking.time_slots');
        $timeSlot = array_rand($timeSlots);

        return [
            'name' => $this->faker->name(),
            'phone' => '09'.$this->faker->numerify('########'),
            'email' => $this->faker->safeEmail(),
            'service_name' => null,
            'preferred_date' => now()->addDays($this->faker->numberBetween(1, 30))->toDateString(),
            'time_slot' => $timeSlot,
            'time_slot_label' => $timeSlots[$timeSlot],
            'party_size' => $this->faker->numberBetween(1, 4),
            'note' => null,
            'status' => BookingStatus::New,
            'admin_note' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0 (Test)',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => BookingStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => BookingStatus::Completed]);
    }
}
