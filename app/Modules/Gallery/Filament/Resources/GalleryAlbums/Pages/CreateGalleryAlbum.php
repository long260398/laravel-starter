<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages;

use App\Modules\Gallery\Actions\SyncGalleryAlbumImages;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryAlbum extends CreateRecord
{
    protected static string $resource = GalleryAlbumResource::class;

    /**
     * @var list<string>
     */
    private array $imagePaths = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->imagePaths = $data['images'] ?? [];

        unset($data['images']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SyncGalleryAlbumImages::class)->handle($this->record, $this->imagePaths);
    }
}
