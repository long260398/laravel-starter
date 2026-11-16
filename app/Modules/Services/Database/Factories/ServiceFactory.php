<?php

namespace App\Modules\Services\Database\Factories;

use App\Enums\PublishStatus;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'short_description' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'price' => null,
            'price_note' => null,
            'duration' => null,
            'is_featured' => false,
            'status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Published,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }
}
