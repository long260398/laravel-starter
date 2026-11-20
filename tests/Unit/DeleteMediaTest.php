<?php

namespace Tests\Unit;

use App\Actions\DeleteMedia;
use App\Exceptions\MediaInUseException;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_the_db_row_and_file_when_unused(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('media', config('media.disk'));
        $media = Media::factory()->create(['disk' => config('media.disk'), 'path' => $path]);

        app(DeleteMedia::class)->handle($media);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_throws_when_media_is_still_in_use_even_when_called_directly(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => $path]);
        $media = Media::query()->where('path', $path)->first();

        $this->expectException(MediaInUseException::class);

        try {
            app(DeleteMedia::class)->handle($media);
        } finally {
            $this->assertDatabaseHas('media', ['id' => $media->id]);
            Storage::disk(config('media.disk'))->assertExists($path);
        }
    }
}
