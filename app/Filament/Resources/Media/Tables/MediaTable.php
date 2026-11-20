<?php

namespace App\Filament\Resources\Media\Tables;

use App\Actions\DeleteMedia;
use App\Exceptions\MediaInUseException;
use App\Filament\Resources\Media\MediaResource;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 5])
            ->recordUrl(fn (Media $record): string => MediaResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                // `contentGrid()` chỉ thực sự đổi sang layout dạng lưới
                // (role="list") khi bảng có "columns layout" — tức ít nhất 1
                // phần tử top-level là Layout\Component (Stack/Grid/Split...).
                // Chỉ truyền cột phẳng (Column thường) thì Filament vẫn render
                // <table> cổ điển dù đã gọi contentGrid().
                Stack::make([
                    ViewColumn::make('card')
                        ->label('')
                        ->view('filament.media.grid-card'),
                    // Không hiển thị trong lưới (tên file ngẫu nhiên không có
                    // giá trị để xem) — chỉ giữ để hộp tìm kiếm của bảng tìm
                    // được theo tên gốc/alt (Table::getColumns() không lọc
                    // theo hidden() khi áp search, xem
                    // Filament\Tables\Concerns\CanSearchRecords).
                    TextColumn::make('original_name')
                        ->searchable()
                        ->hidden(),
                    TextColumn::make('alt')
                        ->searchable()
                        ->hidden(),
                ]),
            ])
            ->filters([
                SelectFilter::make('mime_type')
                    ->label('Loại file')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/webp' => 'WebP',
                        'image/gif' => 'GIF',
                    ]),
                TernaryFilter::make('is_used')
                    ->label('Tình trạng sử dụng')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đang dùng')
                    ->falseLabel('Chưa dùng')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('usages'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('usages'),
                    ),
                SelectFilter::make('uploaded_by')
                    ->label('Người upload')
                    ->relationship('uploader', 'name'),
            ])
            ->recordActions([
                Action::make('delete')
                    ->label('Xóa')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('delete')
                    ->visible(fn (Media $record): bool => ! $record->isUsed())
                    ->action(fn (Media $record) => self::deleteOrNotify($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteUnused')
                        ->label('Xóa ảnh chưa dùng')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize('deleteAny')
                        ->action(function (Collection $records): void {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->isUsed()) {
                                    $skipped++;

                                    continue;
                                }

                                app(DeleteMedia::class)->handle($record);
                                $deleted++;
                            }

                            Notification::make()
                                ->title("Đã xóa {$deleted} file.".($skipped > 0 ? " Bỏ qua {$skipped} file đang dùng." : ''))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    private static function deleteOrNotify(Media $record): void
    {
        try {
            app(DeleteMedia::class)->handle($record);

            Notification::make()->title('Đã xóa file.')->success()->send();
        } catch (MediaInUseException) {
            Notification::make()->title('Không thể xóa: file đang được dùng.')->danger()->send();
        }
    }
}
