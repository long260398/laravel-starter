<?php

namespace Tests\Unit;

use App\Actions\ReleaseMediaUsage;
use App\Models\MediaUsage;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleaseMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_removes_the_usage_row_without_touching_the_file_or_media_row(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => $path]);

        app(ReleaseMediaUsage::class)->handle(config('media.disk'), $path, $post, 'featured_image');

        $this->assertSame(0, MediaUsage::query()->count());
        $this->assertDatabaseHas('media', ['path' => $path]);
        Storage::disk(config('media.disk'))->assertExists($path);
    }

    public function test_releasing_an_unknown_path_is_a_no_op(): void
    {
        $post = Post::factory()->create();

        app(ReleaseMediaUsage::class)->handle(config('media.disk'), 'posts/unknown.jpg', $post, 'featured_image');

        $this->assertSame(0, MediaUsage::query()->count());
    }
}
