<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Enums\PublishStatus;
use App\Models\Banner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Ảnh')
                    ->disk('public'),
                TextColumn::make('title')
                    ->label('Tên nội bộ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Vị trí')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => config("banners.positions.{$state}", $state)),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
                TextColumn::make('schedule')
                    ->label('Khoảng thời gian')
                    ->state(function (Banner $record): string {
                        $startsAt = $record->starts_at?->format('d/m/Y H:i');
                        $endsAt = $record->ends_at?->format('d/m/Y H:i');

                        if ($startsAt === null && $endsAt === null) {
                            return 'Không giới hạn';
                        }

                        return ($startsAt ?? '...').' - '.($endsAt ?? '...');
                    }),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->label('Vị trí')
                    ->options(fn (): array => config('banners.positions', [])),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class),
                TrashedFilter::make()
                    ->label('Thùng rác'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
