<?php

namespace App\Modules\Booking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case CustomerCancelled = 'customer_cancelled';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Confirmed => 'Đã xác nhận',
            self::Completed => 'Hoàn thành',
            self::CustomerCancelled => 'Khách huỷ',
            self::NoShow => 'Không đến',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::Confirmed => 'warning',
            self::Completed => 'success',
            self::CustomerCancelled, self::NoShow => 'gray',
        };
    }
}
