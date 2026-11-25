<?php

namespace App\Modules\Booking\Filament\Resources\Bookings\Schemas;

use App\Modules\Booking\Enums\BookingStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Yêu cầu của khách')
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
                        TextInput::make('service_name')
                            ->label('Dịch vụ')
                            ->disabled(),
                        DatePicker::make('preferred_date')
                            ->label('Ngày mong muốn')
                            ->disabled(),
                        TextInput::make('time_slot_label')
                            ->label('Khung giờ')
                            ->disabled(),
                        TextInput::make('party_size')
                            ->label('Số người')
                            ->disabled(),
                        Textarea::make('note')
                            ->label('Ghi chú của khách')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Xử lý')
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(BookingStatus::class)
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Ghi chú nội bộ')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
