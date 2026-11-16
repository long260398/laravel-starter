<?php

namespace App\Modules\Gallery\Database\Factories;

use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_album_id' => GalleryAlbum::factory(),
            'image' => 'gallery/images/'.$this->faker->uuid().'.jpg',
            'caption' => $this->faker->sentence(),
        ];
    }
}
