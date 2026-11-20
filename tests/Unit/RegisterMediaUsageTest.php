<?php

namespace Tests\Unit;

use App\Actions\RegisterMediaUsage;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_new_file_and_attaches_usage(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => null]);

        app(RegisterMediaUsage::class)->handle(config('media.disk'), $path, $post, 'featured_image');

        $media = Media::query()->where('path', $path)->first();
        $this->assertNotNull($media);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertDatabaseHas('media_usages', [
            'media_id' => $media->id,
            'usable_type' => Post::class,
            'usable_id' => $post->id,
            'field' => 'featured_image',
        ]);
    }

    public function test_calling_twice_does_not_duplicate_media_or_usage_rows(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => null]);

        app(RegisterMediaUsage::class)->handle(config('media.disk'), $path, $post, 'featured_image');
        app(RegisterMediaUsage::class)->handle(config('media.disk'), $path, $post, 'featured_image');

        $this->assertSame(1, Media::query()->where('path', $path)->count());
        $this->assertSame(1, MediaUsage::query()->count());
    }

    public function test_reuses_the_same_media_row_when_two_models_use_the_same_file(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('shared.jpg')->store('posts', config('media.disk'));
        $postA = Post::factory()->create(['featured_image' => null]);
        $postB = Post::factory()->create(['featured_image' => null]);

        app(RegisterMediaUsage::class)->handle(config('media.disk'), $path, $postA, 'featured_image');
        app(RegisterMediaUsage::class)->handle(config('media.disk'), $path, $postB, 'featured_image');

        $this->assertSame(1, Media::query()->where('path', $path)->count());
        $this->assertSame(2, MediaUsage::query()->count());
    }
}
