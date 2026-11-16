<?php

namespace App\Modules\Gallery\Filament\Resources\GalleryAlbums\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('cover_image')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk('public')
                    ->directory('gallery/covers')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }
}
