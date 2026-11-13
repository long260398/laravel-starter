<?php

namespace App\Filament\Resources\StaticPages\Schemas;

use App\Enums\PublishStatus;
use App\Models\StaticPage;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class StaticPageForm
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
                    ->maxLength(255)
                    ->rule(self::notReservedSlugRule())
                    ->helperText('Không được trùng với các đường dẫn hệ thống (admin, storage, sitemap.xml...).'),
                FileUpload::make('featured_image')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk('public')
                    ->directory('static-pages')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                RichEditor::make('content')
                    ->label('Nội dung')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('meta_title')
                    ->label('Meta title')
                    ->maxLength(255)
                    ->helperText('Để trống sẽ dùng SEO mặc định trong Cài đặt website.'),
                Textarea::make('meta_description')
                    ->label('Meta description')
                    ->maxLength(255)
                    ->helperText('Để trống sẽ dùng SEO mặc định trong Cài đặt website.'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }

    private static function notReservedSlugRule(): Closure
    {
        return function () {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (StaticPage::isReservedSlug((string) $value)) {
                    $fail('Đường dẫn này đã được hệ thống sử dụng, vui lòng chọn đường dẫn khác.');
                }
            };
        };
    }
}
