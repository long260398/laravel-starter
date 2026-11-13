<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaticPage>
 */
class StaticPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'title' => $title,
            'slug' => str($title)->slug(),
            'content' => $this->faker->paragraphs(3, true),
            'meta_title' => null,
            'meta_description' => null,
            'status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Published,
        ]);
    }
}
