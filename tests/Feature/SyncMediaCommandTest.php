<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_media_from_models_and_settings(): void
    {
        Storage::fake(config('media.disk'));

        $postPath = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        Post::factory()->create(['featured_image' => $postPath]);

        $logoPath = UploadedFile::fake()->image('logo.png')->store('settings', config('media.disk'));
        Setting::set('logo', $logoPath);

        Artisan::call('media:sync');

        $this->assertDatabaseHas('media', ['path' => $postPath]);
        $this->assertDatabaseHas('media', ['path' => $logoPath]);
        $this->assertSame(2, MediaUsage::query()->count());
    }

    public function test_registers_unknown_disk_files_as_unused_and_skips_disallowed_ones(): void
    {
        Storage::fake(config('media.disk'));

        $disk = Storage::disk(config('media.disk'));
        UploadedFile::fake()->image('orphan.jpg')->storeAs('media/2026/01', 'orphan.jpg', config('media.disk'));
        $disk->put('media/2026/01/.gitignore', '');
        $disk->put('livewire-tmp/abc123.jpg', 'tmp');
        $disk->put('media/2026/01/script.php', '<?php echo 1;');

        Artisan::call('media:sync');
        $output = Artisan::output();

        $this->assertDatabaseHas('media', ['path' => 'media/2026/01/orphan.jpg']);
        $this->assertDatabaseMissing('media', ['path' => 'media/2026/01/.gitignore']);
        $this->assertDatabaseMissing('media', ['path' => 'livewire-tmp/abc123.jpg']);
        $this->assertDatabaseMissing('media', ['path' => 'media/2026/01/script.php']);
        $this->assertStringContainsString('File bị bỏ qua', $output);
    }

    public function test_running_twice_does_not_duplicate_anything(): void
    {
        Storage::fake(config('media.disk'));

        $postPath = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        Post::factory()->create(['featured_image' => $postPath]);

        Artisan::call('media:sync');
        $countAfterFirst = Media::query()->count();
        $usageCountAfterFirst = MediaUsage::query()->count();

        Artisan::call('media:sync');

        $this->assertSame($countAfterFirst, Media::query()->count());
        $this->assertSame($usageCountAfterFirst, MediaUsage::query()->count());
    }

    public function test_reports_media_missing_on_disk(): void
    {
        Storage::fake(config('media.disk'));

        Media::factory()->create(['disk' => config('media.disk'), 'path' => 'media/missing.jpg']);

        Artisan::call('media:sync');
        $output = Artisan::output();

        $this->assertStringContainsString('media/missing.jpg', $output);
    }
}
