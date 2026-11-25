<?php

namespace App\Modules\Booking\Filament\Resources\Bookings\Pages;

use App\Modules\Booking\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
