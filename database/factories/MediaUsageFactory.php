<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaUsage>
 */
class MediaUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'media_id' => Media::factory(),
            'field' => 'image',
        ];
    }
}
