<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Enums\ContactStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung khách gửi')
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ tên')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('subject')
                            ->label('Chủ đề')
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Nội dung')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Xử lý')
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(ContactStatus::class)
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Ghi chú nội bộ')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
