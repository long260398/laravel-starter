<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\RelationManagers;

use App\Modules\Gallery\Models\GalleryImage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Ảnh trong album: quản lý ngay trong trang sửa Album (không phải resource
 * riêng). GalleryImage không có SoftDeletes nên DeleteAction ở đây là xóa
 * vĩnh viễn, tự dọn file qua DeletesStoredFiles.
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Ảnh trong album';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Ảnh')
                    ->image()
                    ->disk('public')
                    ->directory('gallery/images')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->required(),
                TextInput::make('caption')
                    ->label('Chú thích')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Ảnh')
                    ->disk('public'),
                TextColumn::make('caption')
                    ->label('Chú thích')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('uploadMany')
                    ->label('Tải lên nhiều ảnh')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Ảnh')
                            ->multiple()
                            ->image()
                            ->disk('public')
                            ->directory('gallery/images')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->maxFiles(30)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $album = $this->getOwnerRecord();

                        foreach ($data['images'] as $path) {
                            GalleryImage::create([
                                'gallery_album_id' => $album->id,
                                'image' => $path,
                            ]);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
