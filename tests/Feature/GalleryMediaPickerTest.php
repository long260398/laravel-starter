<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\User;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\EditGalleryAlbum;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryMediaPickerTest extends TestCase
{
    use RefreshDatabase;

    private function createLibraryImage(string $name): Media
    {
        $path = UploadedFile::fake()->image($name)->store('media', config('media.disk'));

        return Media::factory()->create([
            'disk' => config('media.disk'),
            'path' => $path,
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_choosing_three_existing_images_appends_them_in_order_without_duplicating_files(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->create());

        $existingPath = UploadedFile::fake()->image('existing.jpg')->store('gallery/images', config('media.disk'));
        $album = GalleryAlbum::factory()->create();
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $existingPath, 'sort_order' => 1]);

        $a = $this->createLibraryImage('a.jpg');
        $b = $this->createLibraryImage('b.jpg');
        $c = $this->createLibraryImage('c.jpg');
        $filesBefore = count(Storage::disk(config('media.disk'))->allFiles());

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(
                TestAction::make('chooseFromLibrary')->schemaComponent('images'),
                data: ['paths' => [$a->path, $b->path, $c->path]],
            )
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$existingPath, $a->path, $b->path, $c->path],
            $album->images()->orderBy('sort_order')->pluck('image')->all(),
        );

        foreach ([$a, $b, $c] as $media) {
            $this->assertTrue(
                MediaUsage::query()->where('media_id', $media->id)->where('field', 'image')->exists(),
            );
            $usage = MediaUsage::query()->where('media_id', $media->id)->where('field', 'image')->first();
            $this->assertStringStartsWith('Ảnh trong album: ', $usage->label());
        }

        $this->assertSame($filesBefore, count(Storage::disk(config('media.disk'))->allFiles()));
    }

    public function test_choosing_an_image_already_in_the_album_is_skipped_as_duplicate(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->create());

        $already = $this->createLibraryImage('already.jpg');
        $album = GalleryAlbum::factory()->create();
        GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $already->path, 'sort_order' => 1]);

        $new = $this->createLibraryImage('new.jpg');

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(
                TestAction::make('chooseFromLibrary')->schemaComponent('images'),
                data: ['paths' => [$already->path, $new->path]],
            )
            ->assertNotified()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$already->path, $new->path],
            $album->images()->orderBy('sort_order')->pluck('image')->all(),
        );
    }

    public function test_selecting_more_than_the_max_files_is_blocked_and_adds_nothing(): void
    {
        Storage::fake(config('media.disk'));
        $this->actingAs(User::factory()->create());

        $album = GalleryAlbum::factory()->create();

        for ($i = 0; $i < 59; $i++) {
            $path = UploadedFile::fake()->image("existing-{$i}.jpg")->store('gallery/images', config('media.disk'));
            GalleryImage::factory()->create(['gallery_album_id' => $album->id, 'image' => $path, 'sort_order' => $i + 1]);
        }

        $extraA = $this->createLibraryImage('extra-a.jpg');
        $extraB = $this->createLibraryImage('extra-b.jpg');

        Livewire::test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
            ->callAction(
                TestAction::make('chooseFromLibrary')->schemaComponent('images'),
                data: ['paths' => [$extraA->path, $extraB->path]],
            )
            ->assertNotified()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(59, $album->images()->count());
        $this->assertFalse($album->images()->where('image', $extraA->path)->exists());
    }
}
