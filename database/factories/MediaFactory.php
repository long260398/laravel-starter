<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'disk' => config('media.disk'),
            'path' => "media/{$name}.jpg",
            'original_name' => "{$name}.jpg",
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 500000),
            'width' => 800,
            'height' => 600,
            'alt' => null,
            'uploaded_by' => null,
        ];
    }
}
