<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_file_column_moves_the_old_files_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', config('media.disk'));

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('posts', config('media.disk'));

        $post->update(['featured_image' => $newPath]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $newPath)->first()->isUsed());
    }

    public function test_clearing_the_file_column_releases_its_usage(): void
    {
        Storage::fake(config('media.disk'));

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', config('media.disk'));

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $post->update(['featured_image' => null]);

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        $this->assertFalse(Media::query()->where('path', $oldPath)->first()->isUsed());
    }

    public function test_old_files_usage_is_kept_when_the_database_update_fails(): void
    {
        Storage::fake(config('media.disk'));

        Post::factory()->create(['slug' => 'slug-da-ton-tai']);

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', config('media.disk'));

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('posts', config('media.disk'));

        try {
            $post->update([
                'slug' => 'slug-da-ton-tai',
                'featured_image' => $newPath,
            ]);

            $this->fail('Expected a QueryException due to the duplicate slug.');
        } catch (QueryException) {
            // Mong đợi lỗi do slug trùng — không bắt tiếp vì chỉ cần xác nhận DB update thất bại.
        }

        Storage::disk(config('media.disk'))->assertExists($oldPath);
        Storage::disk(config('media.disk'))->assertExists($newPath);
        $this->assertTrue(Media::query()->where('path', $oldPath)->first()->isUsed());
    }

    public function test_force_deleting_the_post_releases_its_files_usage_but_keeps_the_file(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('cover.jpg')->store('posts', config('media.disk'));

        $post = Post::factory()->create(['featured_image' => $path]);

        $post->forceDelete();

        Storage::disk(config('media.disk'))->assertExists($path);
        $this->assertFalse(Media::query()->where('path', $path)->first()->isUsed());
    }

    public function test_soft_deleting_the_post_keeps_its_files_usage(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('cover.jpg')->store('posts', config('media.disk'));

        $post = Post::factory()->create(['featured_image' => $path]);

        $post->delete();

        Storage::disk(config('media.disk'))->assertExists($path);
        $this->assertTrue(Media::query()->where('path', $path)->first()->isUsed());
    }
}
