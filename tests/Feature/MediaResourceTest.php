<?php

namespace Tests\Feature;

use App\Actions\RemoveMediaFromHtml;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_media_library(): void
    {
        $response = $this->get('/admin/media');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_media(): void
    {
        $this->actingAs(User::factory()->create());

        $items = Media::factory()->count(3)->create();

        Livewire::test(ListMedia::class)
            ->assertOk()
            ->assertCanSeeTableRecords($items);
    }

    public function test_upload_action_creates_media_rows(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(ListMedia::class)
            ->callAction('upload', data: [
                'files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(2, Media::query()->count());
        $this->assertDatabaseHas('media', ['original_name' => 'a.jpg']);
    }

    public function test_upload_action_rejects_svg(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(ListMedia::class)
            ->callAction('upload', data: [
                'files' => [UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml')],
            ])
            ->assertHasActionErrors(['files']);
    }

    public function test_upload_action_rejects_file_larger_than_2mb(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(ListMedia::class)
            ->callAction('upload', data: [
                'files' => [UploadedFile::fake()->image('too-big.jpg')->size(3000)],
            ])
            ->assertHasActionErrors(['files']);
    }

    public function test_editor_can_edit_alt_text(): void
    {
        $this->actingAs(User::factory()->editor()->create());

        $media = Media::factory()->create(['alt' => null]);

        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->fillForm(['alt' => 'Mô tả ảnh'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Mô tả ảnh', $media->refresh()->alt);
    }

    public function test_editor_cannot_delete_media(): void
    {
        $editor = User::factory()->editor()->create();
        $media = Media::factory()->create();

        $this->assertFalse($editor->can('delete', $media));

        $this->actingAs($editor);

        Livewire::test(ListMedia::class)
            ->assertTableActionHidden('delete', $media);
    }

    public function test_admin_can_delete_unused_media(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $path = UploadedFile::fake()->image('a.jpg')->store('media', config('media.disk'));
        $media = Media::factory()->create(['disk' => config('media.disk'), 'path' => $path]);

        Livewire::test(ListMedia::class)
            ->callTableAction('delete', $media);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_editor_cannot_see_the_force_delete_bulk_action(): void
    {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(ListMedia::class)
            ->assertTableBulkActionHidden('deleteSelected');
    }

    public function test_editor_cannot_execute_the_force_delete_bulk_action_via_a_direct_livewire_call(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->editor()->create());

        $path = UploadedFile::fake()->image('a.jpg')->store('media', config('media.disk'));
        $media = Media::factory()->create(['disk' => config('media.disk'), 'path' => $path]);

        // Gọi trực tiếp các method Livewire bên dưới `callTableBulkAction()`,
        // bỏ qua bước tự assert "action đang hiển thị" của helper test — mô
        // phỏng 1 request bị giả mạo bỏ qua UI. Action phải tự chặn ở server
        // (qua authorize('deleteAny') => isDisabled()), không chỉ ẩn trên UI.
        Livewire::test(ListMedia::class)
            ->call('mountTableBulkAction', 'deleteSelected', [$media->getKey()])
            ->call('callMountedTableBulkAction');

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk(config('media.disk'))->assertExists($path);
    }

    public function test_delete_action_is_visible_but_requires_confirmation_checkbox_for_media_still_in_use(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => $path]);
        $media = Media::query()->where('path', $path)->first();

        Livewire::test(ListMedia::class)
            ->assertTableActionVisible('delete', $media)
            ->callTableAction('delete', $media, data: ['confirm_understand' => false])
            ->assertHasTableActionErrors(['confirm_understand']);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertSame($path, $post->refresh()->featured_image);
    }

    public function test_admin_can_force_delete_media_still_in_use_after_confirming(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $path = UploadedFile::fake()->image('a.jpg')->store('posts', config('media.disk'));
        $post = Post::factory()->create(['featured_image' => $path]);
        $media = Media::query()->where('path', $path)->first();

        Livewire::test(ListMedia::class)
            ->callTableAction('delete', $media, data: ['confirm_understand' => true])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertNull($post->refresh()->featured_image);
        Storage::disk(config('media.disk'))->assertMissing($path);
    }

    public function test_bulk_force_delete_handles_a_mixed_selection_of_used_and_unused_media(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $unusedPath = UploadedFile::fake()->image('unused.jpg')->store('media', config('media.disk'));
        $unused = Media::factory()->create(['disk' => config('media.disk'), 'path' => $unusedPath]);

        $usedPath = UploadedFile::fake()->image('used.jpg')->store('posts', config('media.disk'));
        Post::factory()->create(['featured_image' => $usedPath]);
        $used = Media::query()->where('path', $usedPath)->first();

        Livewire::test(ListMedia::class)
            ->callTableBulkAction('deleteSelected', [$unused, $used], data: ['confirm_understand' => true])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Đã xóa 2/2 ảnh; gỡ khỏi 1 chỗ dùng.');

        $this->assertDatabaseMissing('media', ['id' => $unused->id]);
        $this->assertDatabaseMissing('media', ['id' => $used->id]);
    }

    public function test_bulk_force_delete_reports_partial_success_when_one_item_fails(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $unusedPath = UploadedFile::fake()->image('unused.jpg')->store('media', config('media.disk'));
        $unused = Media::factory()->create(['disk' => config('media.disk'), 'path' => $unusedPath]);

        $contentPath = UploadedFile::fake()->image('content.jpg')->store('content', config('media.disk'));
        Post::factory()->create([
            'content' => '<p><img src="'.Storage::disk(config('media.disk'))->url($contentPath).'"></p>',
        ]);
        $contentMedia = Media::query()->where('path', $contentPath)->first();

        // Giả lập 1 ảnh (gắn trong content) xóa lỗi giữa bulk — ảnh còn lại
        // (chưa dùng) vẫn phải xóa xong và thông báo phải nói đúng cả 2 số.
        $this->app->bind(RemoveMediaFromHtml::class, fn () => throw new RuntimeException('Lỗi giả lập.'));

        Livewire::test(ListMedia::class)
            ->callTableBulkAction('deleteSelected', [$unused, $contentMedia], data: ['confirm_understand' => true])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Đã xóa 1/2 ảnh. 1 ảnh xóa lỗi (xem log).');

        $this->assertDatabaseMissing('media', ['id' => $unused->id]);
        $this->assertDatabaseHas('media', ['id' => $contentMedia->id]);
        Storage::disk(config('media.disk'))->assertExists($contentPath);
    }

    public function test_bulk_delete_only_removes_unused_media(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->admin()->create());

        $unusedPath = UploadedFile::fake()->image('unused.jpg')->store('media', config('media.disk'));
        $unused = Media::factory()->create(['disk' => config('media.disk'), 'path' => $unusedPath]);

        $post = Post::factory()->create(['featured_image' => 'posts/used.jpg']);
        $used = Media::query()->where('path', 'posts/used.jpg')->first();

        Livewire::test(ListMedia::class)
            ->callTableBulkAction('deleteUnused', [$unused, $used]);

        $this->assertDatabaseMissing('media', ['id' => $unused->id]);
        $this->assertDatabaseHas('media', ['id' => $used->id]);
    }

    public function test_filtering_by_usage_status_narrows_results(): void
    {
        $this->actingAs(User::factory()->create());

        $unused = Media::factory()->create();
        $post = Post::factory()->create(['featured_image' => 'posts/used.jpg']);
        $used = Media::query()->where('path', 'posts/used.jpg')->first();

        Livewire::test(ListMedia::class)
            ->filterTable('is_used', true)
            ->assertCanSeeTableRecords([$used])
            ->assertCanNotSeeTableRecords([$unused]);
    }
}
