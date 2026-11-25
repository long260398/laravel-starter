<?php

namespace App\Modules\Booking\Filament\Resources\Bookings\Tables;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('preferred_date')
            ->columns([
                TextColumn::make('name')
                    ->label('Tên')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('SĐT')
                    ->searchable(),
                TextColumn::make('service_name')
                    ->label('Dịch vụ')
                    ->placeholder('—'),
                TextColumn::make('preferred_date')
                    ->label('Ngày hẹn')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('time_slot_label')
                    ->label('Khung giờ'),
                TextColumn::make('party_size')
                    ->label('Số người'),
                SelectColumn::make('status')
                    ->label('Trạng thái')
                    ->options(BookingStatus::class),
                TextColumn::make('created_at')
                    ->label('Ngày gửi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(BookingStatus::class),
                Filter::make('preferred_date')
                    ->label('Ngày hẹn')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('preferred_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('preferred_date', '<=', $date))),
            ])
            ->recordActions([
                Action::make('call')
                    ->label('Gọi điện')
                    ->icon(Heroicon::OutlinedPhone)
                    ->url(fn (Booking $booking): string => self::telLink($booking->phone)),
                Action::make('zalo')
                    ->label('Zalo')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (Booking $booking): string => self::zaloLink($booking->phone))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function telLink(string $phone): string
    {
        return 'tel:'.preg_replace('/\D/', '', $phone);
    }

    private static function zaloLink(string $phone): string
    {
        return 'https://zalo.me/'.preg_replace('/\D/', '', $phone);
    }
}
