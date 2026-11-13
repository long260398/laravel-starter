<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_file_column_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', 'public');

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('posts', 'public');

        $post->update(['featured_image' => $newPath]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_clearing_the_file_column_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', 'public');

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $post->update(['featured_image' => null]);

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_old_file_is_kept_when_the_database_update_fails(): void
    {
        Storage::fake('public');

        Post::factory()->create(['slug' => 'slug-da-ton-tai']);

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', 'public');

        $post = Post::factory()->create(['featured_image' => $oldPath]);

        $newPath = UploadedFile::fake()->image('new.jpg')->store('posts', 'public');

        try {
            $post->update([
                'slug' => 'slug-da-ton-tai',
                'featured_image' => $newPath,
            ]);

            $this->fail('Expected a QueryException due to the duplicate slug.');
        } catch (QueryException) {
            // Mong đợi lỗi do slug trùng — không bắt tiếp vì chỉ cần xác nhận DB update thất bại.
        }

        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_force_deleting_the_post_deletes_the_file(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('cover.jpg')->store('posts', 'public');

        $post = Post::factory()->create(['featured_image' => $path]);

        $post->forceDelete();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_soft_deleting_the_post_keeps_the_file(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('cover.jpg')->store('posts', 'public');

        $post = Post::factory()->create(['featured_image' => $path]);

        $post->delete();

        Storage::disk('public')->assertExists($path);
    }
}
