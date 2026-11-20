<?php

namespace Tests\Feature;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_delete_action_is_hidden_for_media_still_in_use(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $post = Post::factory()->create(['featured_image' => 'posts/a.jpg']);
        $media = Media::query()->where('path', 'posts/a.jpg')->first();

        Livewire::test(ListMedia::class)
            ->assertTableActionHidden('delete', $media);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
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
