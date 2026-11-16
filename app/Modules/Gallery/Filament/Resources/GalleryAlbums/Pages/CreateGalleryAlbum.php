<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages;

use App\Modules\Gallery\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryAlbum extends CreateRecord
{
    protected static string $resource = GalleryAlbumResource::class;
}
