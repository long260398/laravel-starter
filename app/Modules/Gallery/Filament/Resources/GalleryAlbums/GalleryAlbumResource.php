<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums;

use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\CreateGalleryAlbum;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\EditGalleryAlbum;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Pages\ListGalleryAlbums;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\RelationManagers\ImagesRelationManager;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Schemas\GalleryAlbumForm;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\Tables\GalleryAlbumsTable;
use App\Modules\Gallery\Models\GalleryAlbum;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GalleryAlbumResource extends Resource
{
    protected static ?string $model = GalleryAlbum::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'Album ảnh';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Thư viện ảnh';
    }

    public static function getNavigationLabel(): string
    {
        return 'Thư viện ảnh';
    }

    public static function form(Schema $schema): Schema
    {
        return GalleryAlbumForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GalleryAlbumsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryAlbums::route('/'),
            'create' => CreateGalleryAlbum::route('/create'),
            'edit' => EditGalleryAlbum::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
