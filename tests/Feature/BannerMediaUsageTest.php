<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_image_column_moves_the_old_files_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('banners', config('media.disk'));

        $banner = Banner::factory()->create(['image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('banners', config('media.disk'));

        $banner->update(['image' => $newPath]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $newPath)->first()->isUsed());
    }

    public function test_updating_the_image_mobile_column_moves_the_old_files_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old-mobile.jpg')->store('banners', config('media.disk'));

        $banner = Banner::factory()->create(['image_mobile' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new-mobile.jpg')->store('banners', config('media.disk'));

        $banner->update(['image_mobile' => $newPath]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $newPath)->first()->isUsed());
    }

    public function test_clearing_the_image_mobile_column_releases_its_usage(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old-mobile.jpg')->store('banners', config('media.disk'));

        $banner = Banner::factory()->create(['image_mobile' => $oldPath]);

        $banner->update(['image_mobile' => null]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
    }

    public function test_force_deleting_the_banner_releases_usage_of_both_image_files_but_keeps_them(): void
    {
        Storage::fake(config('media.disk'));

        $imagePath = UploadedFile::fake()->image('cover.jpg')->store('banners', config('media.disk'));
        $mobilePath = UploadedFile::fake()->image('cover-mobile.jpg')->store('banners', config('media.disk'));

        $banner = Banner::factory()->create([
            'image' => $imagePath,
            'image_mobile' => $mobilePath,
        ]);

        $banner->forceDelete();

        Storage::disk(config('media.disk'))->assertExists($imagePath);
        Storage::disk(config('media.disk'))->assertExists($mobilePath);
        $this->assertFalse(Media::query()->where('path', $imagePath)->first()->isUsed());
        $this->assertFalse(Media::query()->where('path', $mobilePath)->first()->isUsed());
    }

    public function test_soft_deleting_the_banner_keeps_usage_of_both_image_files(): void
    {
        Storage::fake(config('media.disk'));

        $imagePath = UploadedFile::fake()->image('cover.jpg')->store('banners', config('media.disk'));
        $mobilePath = UploadedFile::fake()->image('cover-mobile.jpg')->store('banners', config('media.disk'));

        $banner = Banner::factory()->create([
            'image' => $imagePath,
            'image_mobile' => $mobilePath,
        ]);

        $banner->delete();

        Storage::disk(config('media.disk'))->assertExists($imagePath);
        Storage::disk(config('media.disk'))->assertExists($mobilePath);
        $this->assertTrue(Media::query()->where('path', $imagePath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $mobilePath)->first()->isUsed());
    }
}
