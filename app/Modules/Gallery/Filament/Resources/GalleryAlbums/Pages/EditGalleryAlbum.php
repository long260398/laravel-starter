<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages;

use App\Modules\Gallery\Actions\SyncGalleryAlbumImages;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryAlbum extends EditRecord
{
    protected static string $resource = GalleryAlbumResource::class;

    /**
     * @var list<string>
     */
    private array $imagePaths = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['images'] = $this->record->images()->orderBy('sort_order')->pluck('image')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->imagePaths = $data['images'] ?? [];

        unset($data['images']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(SyncGalleryAlbumImages::class)->handle($this->record, $this->imagePaths);
    }
}
