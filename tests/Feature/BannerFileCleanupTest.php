<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_image_column_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('banners', 'public');

        $banner = Banner::factory()->create(['image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('banners', 'public');

        $banner->update(['image' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_updating_the_image_mobile_column_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old-mobile.jpg')->store('banners', 'public');

        $banner = Banner::factory()->create(['image_mobile' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new-mobile.jpg')->store('banners', 'public');

        $banner->update(['image_mobile' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_clearing_the_image_mobile_column_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old-mobile.jpg')->store('banners', 'public');

        $banner = Banner::factory()->create(['image_mobile' => $oldPath]);

        $banner->update(['image_mobile' => null]);

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_force_deleting_the_banner_deletes_both_image_files(): void
    {
        Storage::fake('public');

        $imagePath = UploadedFile::fake()->image('cover.jpg')->store('banners', 'public');
        $mobilePath = UploadedFile::fake()->image('cover-mobile.jpg')->store('banners', 'public');

        $banner = Banner::factory()->create([
            'image' => $imagePath,
            'image_mobile' => $mobilePath,
        ]);

        $banner->forceDelete();

        Storage::disk('public')->assertMissing($imagePath);
        Storage::disk('public')->assertMissing($mobilePath);
    }

    public function test_soft_deleting_the_banner_keeps_both_image_files(): void
    {
        Storage::fake('public');

        $imagePath = UploadedFile::fake()->image('cover.jpg')->store('banners', 'public');
        $mobilePath = UploadedFile::fake()->image('cover-mobile.jpg')->store('banners', 'public');

        $banner = Banner::factory()->create([
            'image' => $imagePath,
            'image_mobile' => $mobilePath,
        ]);

        $banner->delete();

        Storage::disk('public')->assertExists($imagePath);
        Storage::disk('public')->assertExists($mobilePath);
    }
}
