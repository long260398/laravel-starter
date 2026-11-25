<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Forms\Components\MediaPicker;
use App\Models\PostCategory;
use App\Models\Tag;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
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
                    ->maxLength(255)
                    ->rules(['not_in:search,category,tag'])
                    ->validationMessages([
                        'not_in' => 'Đường dẫn không được là "search", "category" hoặc "tag".',
                    ]),
                Select::make('category_id')
                    ->label('Chuyên mục')
                    ->options(fn (): array => PostCategory::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->native(false),
                TagsInput::make('tag_names')
                    ->label('Thẻ')
                    ->suggestions(fn (): array => Tag::query()->pluck('name')->all()),
                Textarea::make('excerpt')
                    ->label('Tóm tắt')
                    ->maxLength(300)
                    ->rows(3)
                    ->helperText('Dùng làm mô tả SEO và hiển thị trên danh sách bài viết. Để trống sẽ tự trích từ nội dung.')
                    ->columnSpanFull(),
                MediaPicker::make('featured_image')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk('public')
                    ->directory('posts')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                RichEditor::make('content')
                    ->label('Nội dung')
                    ->required()
                    ->fileAttachmentsDisk(config('media.disk'))
                    ->fileAttachmentsDirectory('content')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
                DateTimePicker::make('published_at')
                    ->label('Ngày xuất bản')
                    ->native(false),
            ]);
    }
}
