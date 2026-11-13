<?php

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '09'.$this->faker->numerify('########'),
            'email' => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(),
            'status' => ContactStatus::New,
            'admin_note' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0 (Test)',
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContactStatus::Processing,
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContactStatus::Processed,
        ]);
    }
}
