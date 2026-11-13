<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'subtitle' => null,
            'image' => 'banners/'.$this->faker->uuid().'.jpg',
            'image_mobile' => null,
            'link_url' => null,
            'open_in_new_tab' => false,
            'button_text' => null,
            'position' => 'home_slider',
            'starts_at' => null,
            'ends_at' => null,
            'status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Published,
        ]);
    }

    public function forPosition(string $position): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => $position,
        ]);
    }
}
