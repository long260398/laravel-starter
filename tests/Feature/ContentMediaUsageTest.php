<?php

namespace Tests\Feature;

use App\Actions\DeleteMedia;
use App\Enums\PublishStatus;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentMediaUsageTest extends TestCase
{
    use RefreshDatabase;

    private function imgTag(string $path): string
    {
        return '<img src="'.Storage::disk(config('media.disk'))->url($path).'">';
    }

    public function test_saving_content_registers_usage_for_every_embedded_image(): void
    {
        Storage::fake(config('media.disk'));

        $pathA = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $pathB = UploadedFile::fake()->image('b.jpg')->store('content', config('media.disk'));

        $post = Post::factory()->create([
            'content' => '<p>'.$this->imgTag($pathA).'</p><p>'.$this->imgTag($pathB).'</p>',
        ]);

        $this->assertTrue(Media::query()->where('path', $pathA)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $pathB)->first()->isUsed());

        $this->assertDatabaseHas('media_usages', [
            'usable_type' => Post::class,
            'usable_id' => $post->id,
            'field' => 'content',
        ]);
    }

    public function test_updating_content_releases_usage_of_images_no_longer_referenced(): void
    {
        Storage::fake(config('media.disk'));

        $pathA = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $pathB = UploadedFile::fake()->image('b.jpg')->store('content', config('media.disk'));

        $post = Post::factory()->create(['content' => '<p>'.$this->imgTag($pathA).'</p>']);

        $post->update(['content' => '<p>'.$this->imgTag($pathB).'</p>']);

        $this->assertFalse(Media::query()->where('path', $pathA)->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', $pathB)->first()->isUsed());
        Storage::disk(config('media.disk'))->assertExists($pathA);
    }

    public function test_an_external_image_url_in_content_is_not_registered(): void
    {
        Storage::fake(config('media.disk'));

        Post::factory()->create([
            'content' => '<p><img src="https://example.com/outside.jpg"></p>',
        ]);

        $this->assertSame(0, Media::query()->count());
    }

    public function test_force_deleting_a_media_embedded_in_content_strips_the_img_tag(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $post = Post::factory()->create([
            'content' => '<p>Mở đầu</p><p>'.$this->imgTag($path).'</p><p>Kết thúc</p>',
        ]);

        $media = Media::query()->where('path', $path)->first();

        app(DeleteMedia::class)->handle($media, force: true);

        $post->refresh();
        $this->assertStringNotContainsString('<img', $post->content);
        $this->assertStringContainsString('Mở đầu', $post->content);
        $this->assertStringContainsString('Kết thúc', $post->content);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertDatabaseMissing('media_usages', [
            'usable_type' => Post::class,
            'usable_id' => $post->id,
            'field' => 'content',
        ]);
    }

    public function test_force_deleting_the_post_releases_usage_of_images_embedded_in_content(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $post = Post::factory()->create(['content' => '<p>'.$this->imgTag($path).'</p>']);

        $post->forceDelete();

        $this->assertFalse(Media::query()->where('path', $path)->first()->isUsed());
        Storage::disk(config('media.disk'))->assertExists($path);
    }

    public function test_removing_an_embedded_image_keeps_vietnamese_text_and_the_other_image_intact(): void
    {
        Storage::fake(config('media.disk'));

        $pathToRemove = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $pathToKeep = UploadedFile::fake()->image('b.jpg')->store('content', config('media.disk'));

        $post = Post::factory()->create([
            'content' => '<p>Chăm sóc da mặt – ưu đãi</p>'
                .'<p>'.$this->imgTag($pathToRemove).'</p>'
                .'<p>'.$this->imgTag($pathToKeep).'</p>',
        ]);

        $media = Media::query()->where('path', $pathToRemove)->first();

        app(DeleteMedia::class)->handle($media, force: true);

        $content = $post->refresh()->content;
        $this->assertStringContainsString('Chăm sóc da mặt – ưu đãi', $content);
        $this->assertStringContainsString($pathToKeep, $content);
        $this->assertStringNotContainsString($pathToRemove, $content);
        $this->assertSame(1, substr_count($content, '<img'));
    }

    public function test_deleting_media_does_not_match_a_similarly_named_path_as_a_substring(): void
    {
        Storage::fake(config('media.disk'));

        $target = UploadedFile::fake()->image('a.jpg')->storeAs('posts', 'a.jpg', config('media.disk'));
        $similar = UploadedFile::fake()->image('aa.jpg')->storeAs('posts', 'aa.jpg', config('media.disk'));
        $nested = UploadedFile::fake()->image('a2.jpg')->storeAs('x/posts', 'a.jpg', config('media.disk'));

        $post = Post::factory()->create([
            'content' => '<p>'.$this->imgTag($target).'</p>'
                .'<p>'.$this->imgTag($similar).'</p>'
                .'<p>'.$this->imgTag($nested).'</p>',
        ]);

        $media = Media::query()->where('path', $target)->first();

        app(DeleteMedia::class)->handle($media, force: true);

        $content = $post->refresh()->content;
        $this->assertStringNotContainsString(Storage::disk(config('media.disk'))->url($target), $content);
        $this->assertStringContainsString(Storage::disk(config('media.disk'))->url($similar), $content);
        $this->assertStringContainsString(Storage::disk(config('media.disk'))->url($nested), $content);
        Storage::disk(config('media.disk'))->assertExists($similar);
        Storage::disk(config('media.disk'))->assertExists($nested);
    }

    public function test_matching_prefers_data_id_exactly_like_filaments_real_markup(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));

        // Markup thật của Filament RichEditor sau khi upload: `data-id` chứa
        // path thật, `src` là URL đã resolve — xem
        // vendor/filament/forms/src/Components/RichEditor.php::resolveFileAttachmentIds().
        $realMarkup = '<img src="'.Storage::disk(config('media.disk'))->url($path).'" data-id="'.$path.'" width="1280" height="720">';

        $post = Post::factory()->create(['content' => '<p>'.$realMarkup.'</p>']);

        $this->assertTrue(Media::query()->where('path', $path)->first()->isUsed());

        $media = Media::query()->where('path', $path)->first();
        app(DeleteMedia::class)->handle($media, force: true);

        $content = $post->refresh()->content;
        $this->assertStringNotContainsString('<img', $content);
    }

    public function test_removing_the_only_image_in_a_paragraph_removes_the_now_empty_paragraph(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));
        $post = Post::factory()->create([
            'content' => '<p>Trước</p><p>'.$this->imgTag($path).'</p><p>Sau</p>',
        ]);

        $media = Media::query()->where('path', $path)->first();
        app(DeleteMedia::class)->handle($media, force: true);

        $content = $post->refresh()->content;
        $this->assertSame('<p>Trước</p><p>Sau</p>', $content);
    }

    public function test_removing_an_embedded_image_does_not_change_published_at(): void
    {
        Storage::fake(config('media.disk'));

        $path = UploadedFile::fake()->image('a.jpg')->store('content', config('media.disk'));

        // Giả lập dữ liệu cũ bất thường: status Published nhưng published_at
        // null (vd import thủ công, bỏ qua Eloquent event) — đúng điều kiện
        // khiến hook `saving` của Post tự set published_at = now(). Gỡ ảnh
        // trong content tuyệt đối không được kích hoạt lại hook đó.
        $post = Post::factory()->create(['content' => '<p>'.$this->imgTag($path).'</p>']);
        DB::table('posts')->where('id', $post->id)->update([
            'status' => PublishStatus::Published->value,
            'published_at' => null,
        ]);

        $media = Media::query()->where('path', $path)->first();
        app(DeleteMedia::class)->handle($media, force: true);

        $this->assertNull($post->refresh()->published_at);
    }
}
