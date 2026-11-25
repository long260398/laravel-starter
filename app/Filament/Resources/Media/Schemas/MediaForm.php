<?php

namespace App\Filament\Resources\Media\Schemas;

use App\Models\Media;
use App\Models\MediaUsage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ảnh')
                    ->schema([
                        Image::make(
                            fn (Media $record): string => $record->url ?? '',
                            fn (Media $record): string => $record->alt ?? $record->original_name,
                        )->imageHeight(200),
                    ]),
                Section::make('Thông tin file')
                    ->schema([
                        TextInput::make('alt')
                            ->label('Văn bản thay thế (alt)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Text::make(fn (Media $record): string => 'Tên gốc: '.$record->original_name),
                        Text::make(fn (Media $record): string => 'Loại file: '.$record->mime_type),
                        Text::make(fn (Media $record): string => 'Dung lượng: '.number_format($record->size / 1024, 1).' KB'),
                        Text::make(fn (Media $record): ?string => ($record->width && $record->height)
                            ? "Kích thước ảnh: {$record->width}×{$record->height}"
                            : null),
                        Text::make(fn (Media $record): string => 'Người upload: '.($record->uploader?->name ?? 'Không rõ')),
                        Text::make(fn (Media $record): string => 'Ngày tải lên: '.$record->created_at->format('d/m/Y H:i')),
                    ])
                    ->columns(2),
                Section::make('Đang dùng ở')
                    ->schema(fn (?Media $record): array => self::usageRows($record)),
            ]);
    }

    /**
     * @return array<int, Text>
     */
    public static function usageRows(?Media $record): array
    {
        $usages = $record?->usages ?? collect();

        if ($usages->isEmpty()) {
            return [Text::make('Chưa được dùng ở đâu — có thể xóa.')->color('gray')];
        }

        return $usages->map(function (MediaUsage $usage): Text {
            $label = $usage->label();
            $url = $usage->url();

            if ($url === null) {
                return Text::make($label);
            }

            return Text::make(new HtmlString(
                '<a href="'.e($url).'" class="underline" target="_blank" rel="noopener">'.e($label).'</a>'
            ));
        })->values()->all();
    }
}
