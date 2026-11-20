<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Forms\Components\MediaPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GalleryAlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tên album')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set, callable $get): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Đường dẫn (slug)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Mô tả')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
                MediaPicker::make('images', multipleActionLabel: 'Thêm vào album')
                    ->label('Ảnh trong album')
                    ->helperText('Ảnh đầu tiên sẽ là ảnh bìa — kéo thả để đổi thứ tự.')
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->panelLayout('grid')
                    ->image()
                    ->disk(config('media.disk'))
                    ->directory('gallery/images')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->maxFiles(60)
                    ->columnSpanFull(),
            ]);
    }
}
