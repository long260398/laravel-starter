<?php

namespace App\Modules\Services\Filament\Resources\Services\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Forms\Components\MediaPicker;
use App\Modules\Services\Models\ServiceCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_category_id')
                    ->label('Danh mục')
                    ->options(fn (): array => ServiceCategory::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->required()
                    ->native(false),
                TextInput::make('name')
                    ->label('Tên dịch vụ')
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
                TextInput::make('short_description')
                    ->label('Mô tả ngắn')
                    ->maxLength(255),
                MediaPicker::make('image')
                    ->label('Hình ảnh')
                    ->image()
                    ->disk('public')
                    ->directory('services')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                RichEditor::make('content')
                    ->label('Nội dung')
                    ->required()
                    ->fileAttachmentsDisk(config('media.disk'))
                    ->fileAttachmentsDirectory('content')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Giá (VNĐ)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Để trống nếu muốn hiển thị "Liên hệ".'),
                TextInput::make('price_note')
                    ->label('Chú thích giá')
                    ->maxLength(50)
                    ->helperText('Bắt đầu bằng "/" sẽ hiển thị ngay sau giá (vd "/buổi"). Ngược lại hiển thị trước giá (vd "Từ").'),
                TextInput::make('duration')
                    ->label('Thời gian thực hiện')
                    ->maxLength(50)
                    ->placeholder('Vd: 60 phút'),
                Toggle::make('is_featured')
                    ->label('Dịch vụ nổi bật'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }
}
