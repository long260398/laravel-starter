<?php

namespace Tests\Unit;

use App\Actions\DeleteMedia;
use App\Actions\RemoveMediaFromHtml;
use App\Enums\PublishStatus;
use App\Exceptions\MediaInUseException;
use App\Models\Banner;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Post;
use App\Models\Setting;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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

    public function test_force_deleting_a_media_used_as_the_logo_nulls_the_setting_and_keeps_deleting(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('logo.png')->store('settings', config('media.disk'));
        Setting::set('logo', $path);
        $media = Media::query()->where('path', $path)->first();

        $result = app(DeleteMedia::class)->handle($media, force: true);

        $this->assertSame(1, $result['detached']);
        $this->assertSame([], $result['notes']);
        $this->assertNull(Setting::get('logo'));
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_force_deleting_a_required_banner_image_drafts_the_banner_instead_of_nulling_it(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('cover.jpg')->store('banners', config('media.disk'));
        $banner = Banner::factory()->create(['title' => 'Khuyến mãi hè', 'image' => $path, 'status' => PublishStatus::Published]);
        $media = Media::query()->where('path', $path)->first();

        $result = app(DeleteMedia::class)->handle($media, force: true);

        $this->assertSame(1, $result['detached']);
        $this->assertNotEmpty($result['notes']);
        $this->assertSame(PublishStatus::Draft, $banner->refresh()->status);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_force_deleting_a_gallery_image_removes_the_row_instead_of_nulling_it(): void
    {
        Storage::fake(config('media.disk'));

        $album = GalleryAlbum::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', config('media.disk'));
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $path]);
        $media = Media::query()->where('path', $path)->first();

        $result = app(DeleteMedia::class)->handle($media, force: true);

        $this->assertSame(1, $result['detached']);
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_force_deleting_a_nullable_file_column_nulls_it(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => $path]);
        $media = Media::query()->where('path', $path)->first();

        app(DeleteMedia::class)->handle($media, force: true);

        $this->assertNull($post->refresh()->featured_image);
    }

    public function test_an_error_mid_transaction_rolls_back_every_change_and_keeps_the_file(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('shared.jpg')->store('shared', config('media.disk'));

        $banner = Banner::factory()->create(['image_mobile' => $path]);
        $post = Post::factory()->create([
            'content' => '<p><img src="'.Storage::disk(config('media.disk'))->url($path).'"></p>',
        ]);

        $media = Media::query()->where('path', $path)->first();
        $this->assertSame(2, $media->usages()->count());

        $this->app->bind(RemoveMediaFromHtml::class, fn () => throw new RuntimeException('Lỗi giả lập giữa transaction.'));

        try {
            app(DeleteMedia::class)->handle($media, force: true);
            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (RuntimeException) {
            // mong đợi
        }

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertSame(2, MediaUsage::query()->where('media_id', $media->id)->count());
        $this->assertSame($path, $banner->refresh()->image_mobile);
        $this->assertStringContainsString('<img', $post->refresh()->content);
        Storage::disk(config('media.disk'))->assertExists($path);
    }
}
