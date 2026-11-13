<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\PublishStatus;
use App\Models\Banner;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tên nội bộ')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Dùng để quản lý trong admin, không hiển thị ra frontend.'),
                TextInput::make('subtitle')
                    ->label('Phụ đề')
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Ảnh banner')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('banners')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                FileUpload::make('image_mobile')
                    ->label('Ảnh banner (mobile)')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->helperText('Để trống sẽ dùng ảnh banner chính cho mobile.'),
                TextInput::make('link_url')
                    ->label('Đường dẫn liên kết')
                    ->maxLength(2048)
                    ->rule(self::safeLinkUrlRule())
                    ->helperText('Đường dẫn nội bộ bắt đầu bằng "/" hoặc URL đầy đủ http(s)://'),
                Toggle::make('open_in_new_tab')
                    ->label('Mở liên kết ở tab mới'),
                TextInput::make('button_text')
                    ->label('Chữ trên nút')
                    ->maxLength(255),
                Select::make('position')
                    ->label('Vị trí hiển thị')
                    ->options(fn (): array => config('banners.positions', []))
                    ->required()
                    ->native(false)
                    ->rule(Rule::in(array_keys(config('banners.positions', [])))),
                DateTimePicker::make('starts_at')
                    ->label('Bắt đầu hiển thị')
                    ->native(false)
                    ->helperText('Để trống để hiển thị ngay khi xuất bản.'),
                DateTimePicker::make('ends_at')
                    ->label('Kết thúc hiển thị')
                    ->native(false)
                    ->rule(self::endsAtAfterStartsAtRule())
                    ->helperText('Để trống để không giới hạn thời gian kết thúc.'),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }

    private static function safeLinkUrlRule(): Closure
    {
        return function () {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! Banner::isSafeLinkUrl((string) $value)) {
                    $fail('Đường dẫn không hợp lệ hoặc không an toàn.');
                }
            };
        };
    }

    private static function endsAtAfterStartsAtRule(): Closure
    {
        return function (Get $get) {
            return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                if (blank($value) || blank($get('starts_at'))) {
                    return;
                }

                if (Carbon::parse($value)->lessThanOrEqualTo(Carbon::parse($get('starts_at')))) {
                    $fail('Thời gian kết thúc phải sau thời gian bắt đầu.');
                }
            };
        };
    }
}
