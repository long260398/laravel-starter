<?php

namespace App\Modules\Testimonials\Filament\Resources\Testimonials\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')
                    ->label('Tên khách hàng')
                    ->required()
                    ->maxLength(255),
                TextInput::make('customer_title')
                    ->label('Chức danh / Mô tả')
                    ->maxLength(255)
                    ->placeholder('Vd: Khách hàng thân thiết'),
                FileUpload::make('avatar')
                    ->label('Ảnh đại diện')
                    ->image()
                    ->disk('public')
                    ->directory('testimonials')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                Textarea::make('content')
                    ->label('Nội dung cảm nhận')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->columnSpanFull(),
                Select::make('rating')
                    ->label('Số sao')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->native(false),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }
}
