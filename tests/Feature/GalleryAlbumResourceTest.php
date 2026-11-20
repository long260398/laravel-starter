<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\CreateGalleryAlbum;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\EditGalleryAlbum;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\ListGalleryAlbums;
use App\Modules\Gallery\Models\GalleryAlbum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryAlbumResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_gallery_albums(): void
    {
        $response = $this->get('/admin/gallery-albums');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_gallery_albums(): void
    {
        $this->actingAs(User::factory()->create());

        $albums = GalleryAlbum::factory()->count(3)->create();

        Livewire::test(ListGalleryAlbums::class)
            ->assertOk()
            ->assertCanSeeTableRecords($albums);
    }

    public function test_can_create_gallery_album_with_slug_generated_from_title(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateGalleryAlbum::class)
            ->fillForm([
                'title' => 'Chuyến đi biển',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $album = GalleryAlbum::query()->where('title', 'Chuyến đi biển')->first();

        $this->assertNotNull($album);
        $this->assertSame(Str::slug('Chuyến đi biển'), $album->slug);
    }

    public function test_can_create_gallery_album_with_images_in_submitted_order(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateGalleryAlbum::class)
            ->fillForm([
                'title' => 'Album có ảnh',
                'status' => PublishStatus::Draft,
                'images' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $album = GalleryAlbum::query()->where('title', 'Album có ảnh')->first();

        $this->assertNotNull($album);
        $this->assertSame(3, $album->images()->count());
        $this->assertSame([1, 2, 3], $album->images()->orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_album_images_field_rejects_more_than_sixty_files(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->create());

        $files = [];

        for ($i = 0; $i < 61; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.jpg");
        }

        Livewire::test(CreateGalleryAlbum::class)
            ->fillForm([
                'title' => 'Album quá nhiều ảnh',
                'status' => PublishStatus::Draft,
                'images' => $files,
            ])
            ->call('create')
            ->assertHasFormErrors(['images']);
    }

    public function test_album_images_field_rejects_file_larger_than_2mb(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateGalleryAlbum::class)
            ->fillForm([
                'title' => 'Album ảnh quá nặng',
                'status' => PublishStatus::Draft,
                'images' => [UploadedFile::fake()->image('too-big.jpg')->size(3000)],
            ])
            ->call('create')
            ->assertHasFormErrors(['images']);
    }

    public function test_album_images_field_rejects_non_image_mime_type(): void
    {
        Storage::fake(config('media.disk'));

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateGalleryAlbum::class)
            ->fillForm([
                'title' => 'Album file lạ',
                'status' => PublishStatus::Draft,
                'images' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasFormErrors(['images']);
    }

    public function test_can_update_gallery_album(): void
    {
        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->fillForm([
                'title' => 'Tên đã sửa',
                'slug' => 'ten-da-sua',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('gallery_albums', [
            'id' => $album->id,
            'title' => 'Tên đã sửa',
            'slug' => 'ten-da-sua',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_gallery_album(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('gallery_albums', ['id' => $album->id]);

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('gallery_albums', ['id' => $album->id, 'deleted_at' => null]);

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_gallery_album(): void
    {
        $editor = User::factory()->editor()->create();
        $album = GalleryAlbum::factory()->create();

        $this->assertTrue($editor->can('viewAny', GalleryAlbum::class));
        $this->assertTrue($editor->can('update', $album));
        $this->assertTrue($editor->can('delete', $album));
        $this->assertFalse($editor->can('forceDelete', $album));
    }

    public function test_admin_can_force_delete_gallery_album(): void
    {
        $admin = User::factory()->admin()->create();
        $album = GalleryAlbum::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $album));
    }
}
