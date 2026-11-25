<?php

namespace App\Filament\Resources\Media\Tables;

use App\Actions\DeleteMedia;
use App\Exceptions\MediaInUseException;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Schemas\MediaForm;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

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
                    ->modalHeading(fn (Media $record): string => $record->isUsed() ? 'Xóa ảnh đang được sử dụng' : 'Xóa file')
                    ->modalDescription(fn (Media $record): ?string => $record->isUsed()
                        ? 'Ảnh này đang được dùng ở các chỗ dưới đây. Xóa sẽ gỡ ảnh khỏi những chỗ đó — hành động không thể hoàn tác.'
                        : null)
                    ->schema(fn (Media $record): array => self::usageConfirmationSchema($record))
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
                    BulkAction::make('deleteSelected')
                        ->label('Xóa ảnh đã chọn (cả đang dùng)')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize('deleteAny')
                        ->modalHeading('Xóa ảnh đã chọn')
                        ->modalDescription(fn (Collection $records): string => self::bulkDescription($records))
                        ->schema(fn (Collection $records): array => self::bulkConfirmationSchema($records))
                        ->action(function (Collection $records): void {
                            $deleted = 0;
                            $failed = 0;
                            $detachedTotal = 0;
                            $notes = [];

                            // Mỗi ảnh xóa trong 1 transaction riêng (DeleteMedia) — lỗi ở 1
                            // ảnh không được làm mất thông báo về N ảnh ĐÃ xóa (và file của
                            // chúng ĐÃ mất thật) trước đó trong vòng lặp này.
                            foreach ($records as $record) {
                                try {
                                    $result = app(DeleteMedia::class)->handle($record, force: true);
                                    $deleted++;
                                    $detachedTotal += $result['detached'];
                                    $notes = [...$notes, ...$result['notes']];
                                } catch (Throwable $exception) {
                                    $failed++;
                                    Log::error('Lỗi khi xóa cưỡng ép 1 media trong bulk action', [
                                        'admin_id' => Auth::id(),
                                        'media_id' => $record->id,
                                        'error' => $exception->getMessage(),
                                    ]);
                                }
                            }

                            $title = "Đã xóa {$deleted}/{$records->count()} ảnh"
                                .($detachedTotal > 0 ? "; gỡ khỏi {$detachedTotal} chỗ dùng" : '').'.';

                            if ($failed > 0) {
                                $title .= " {$failed} ảnh xóa lỗi (xem log).";
                            }

                            if ($notes !== []) {
                                $title .= ' '.implode(' ', $notes);
                            }

                            Notification::make()->title($title)->color($failed > 0 ? 'warning' : 'success')->send();
                        }),
                ]),
            ]);
    }

    /**
     * @return array<int, Text|Checkbox>
     */
    private static function usageConfirmationSchema(Media $record): array
    {
        if (! $record->isUsed()) {
            return [];
        }

        return [
            ...MediaForm::usageRows($record),
            Checkbox::make('confirm_understand')
                ->label('Tôi hiểu các chỗ trên sẽ mất ảnh')
                ->accepted()
                ->required(),
        ];
    }

    /**
     * @param  Collection<int, Media>  $records
     */
    private static function bulkDescription(Collection $records): string
    {
        $used = $records->filter(fn (Media $record): bool => $record->isUsed());

        if ($used->isEmpty()) {
            return "Xóa {$records->count()} ảnh đã chọn? Hành động không thể hoàn tác.";
        }

        return "Trong {$records->count()} ảnh đã chọn, có {$used->count()} ảnh đang được dùng ở ít nhất 1 chỗ."
            .' Xóa sẽ gỡ ảnh khỏi những chỗ đó (bản ghi thiếu ảnh bắt buộc sẽ chuyển về Nháp) — hành động không thể hoàn tác.';
    }

    /**
     * @param  Collection<int, Media>  $records
     * @return array<int, Checkbox>
     */
    private static function bulkConfirmationSchema(Collection $records): array
    {
        if ($records->every(fn (Media $record): bool => ! $record->isUsed())) {
            return [];
        }

        return [
            Checkbox::make('confirm_understand')
                ->label('Tôi hiểu các chỗ trên sẽ mất ảnh')
                ->accepted()
                ->required(),
        ];
    }

    private static function deleteOrNotify(Media $record): void
    {
        try {
            $result = app(DeleteMedia::class)->handle($record, force: $record->isUsed());

            $title = $result['detached'] > 0
                ? "Đã xóa file. Gỡ khỏi {$result['detached']} chỗ dùng."
                : 'Đã xóa file.';

            if ($result['notes'] !== []) {
                $title .= ' '.implode(' ', $result['notes']);
            }

            Notification::make()->title($title)->success()->send();
        } catch (MediaInUseException) {
            Notification::make()->title('Không thể xóa: file đang được dùng.')->danger()->send();
        }
    }
}
