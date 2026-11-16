<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\EditGalleryAlbum;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\RelationManagers\ImagesRelationManager;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryImageRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_images_of_an_album(): void
    {
        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();
        $images = GalleryImage::factory()->count(3)->create(['gallery_album_id' => $album->id]);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($images);
    }

    public function test_can_create_single_image_via_form(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'image' => UploadedFile::fake()->image('photo.jpg'),
                'caption' => 'Ảnh đầu tiên',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(1, $album->images()->count());
        $this->assertDatabaseHas('gallery_images', [
            'gallery_album_id' => $album->id,
            'caption' => 'Ảnh đầu tiên',
        ]);
    }

    public function test_images_are_assigned_incrementing_sort_order_per_album(): void
    {
        $album = GalleryAlbum::factory()->create();

        $first = GalleryImage::factory()->create(['gallery_album_id' => $album->id]);
        $second = GalleryImage::factory()->create(['gallery_album_id' => $album->id]);

        $this->assertSame(1, $first->sort_order);
        $this->assertSame(2, $second->sort_order);
    }

    public function test_bulk_upload_action_creates_multiple_images(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('uploadMany')->table(), data: [
                'images' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                ],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(3, $album->images()->count());
    }

    public function test_bulk_upload_rejects_more_than_thirty_files(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        $files = [];

        for ($i = 0; $i < 31; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.jpg");
        }

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('uploadMany')->table(), data: ['images' => $files])
            ->assertHasActionErrors(['images']);

        $this->assertSame(0, $album->images()->count());
    }

    public function test_bulk_upload_rejects_file_larger_than_2mb(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('uploadMany')->table(), data: [
                'images' => [UploadedFile::fake()->image('too-big.jpg')->size(3000)],
            ])
            ->assertHasActionErrors(['images']);

        $this->assertSame(0, $album->images()->count());
    }

    public function test_bulk_upload_rejects_non_image_mime_type(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('uploadMany')->table(), data: [
                'images' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')],
            ])
            ->assertHasActionErrors(['images']);

        $this->assertSame(0, $album->images()->count());
    }

    public function test_deleting_an_image_removes_its_file(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('gallery/images', 'public');
        $image = GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $path]);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $album,
            'pageClass' => EditGalleryAlbum::class,
        ])
            ->callAction(TestAction::make('delete')->table($image));

        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
