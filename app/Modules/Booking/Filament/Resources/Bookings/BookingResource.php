<?php

namespace App\Modules\Booking\Filament\Resources\Bookings;

use App\Enums\NavigationGroup;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Filament\Resources\Bookings\Pages\EditBooking;
use App\Modules\Booking\Filament\Resources\Bookings\Pages\ListBookings;
use App\Modules\Booking\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Modules\Booking\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Modules\Booking\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Customers;

    public static function getModelLabel(): string
    {
        return 'Đặt lịch';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Đặt lịch';
    }

    public static function getNavigationLabel(): string
    {
        return 'Đặt lịch';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', BookingStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
