<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tiêu đề')
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
                FileUpload::make('featured_image')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk('public')
                    ->directory('posts')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                RichEditor::make('content')
                    ->label('Nội dung')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PostStatus::class)
                    ->required()
                    ->native(false),
                DateTimePicker::make('published_at')
                    ->label('Ngày xuất bản')
                    ->native(false),
            ]);
    }
}
