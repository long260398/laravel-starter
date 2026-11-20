<?php

namespace App\Filament\Forms\Components;

use App\Models\Media;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Ô ảnh chuẩn của admin (xem docs/STANDARDS.md mục "Lưu trữ file"): decorator
 * trả về `FileUpload` bình thường (giữ nguyên `->image()->disk()->acceptedFileTypes()...`
 * của từng chỗ gọi), chỉ thêm nút "Chọn từ thư viện" (hint action) và khóa
 * chống path giả qua `preventFilePathTampering()` có sẵn của Filament.
 *
 * `$currentValueResolver` chỉ cần truyền cho field KHÔNG gắn `$record` (ví dụ
 * trang Settings dạng key–value) — field thuộc resource (Post, Banner...) đã
 * được Filament tự cho phép giữ nguyên giá trị gốc của `$record` khi lưu.
 *
 * Khi caller gọi thêm `->multiple()` (vd album Gallery), nút đổi sang chọn
 * nhiều (checkbox) và NỐI các ảnh mới chọn vào cuối danh sách hiện có thay vì
 * ghi đè — xem {@see chooseMultipleFromLibraryAction()}.
 */
class MediaPicker
{
    public static function make(string $name, ?Closure $currentValueResolver = null, ?string $multipleActionLabel = null): FileUpload
    {
        $fileUpload = FileUpload::make($name);

        return $fileUpload
            ->preventFilePathTampering(allowFilePathUsing: function (string $file) use ($fileUpload, $currentValueResolver): bool {
                if (self::existsInLibrary($file, $fileUpload)) {
                    return true;
                }

                return $currentValueResolver !== null && $file === $currentValueResolver();
            })
            ->hintAction(fn (): Action => $fileUpload->isMultiple()
                ? self::chooseMultipleFromLibraryAction($fileUpload, $multipleActionLabel ?? 'Thêm ảnh đã chọn')
                : self::chooseOneFromLibraryAction($fileUpload));
    }

    private static function existsInLibrary(string $path, FileUpload $fileUpload): bool
    {
        $mimeTypes = $fileUpload->getAcceptedFileTypes() ?? [];

        return Media::query()
            ->where('disk', $fileUpload->getDiskName())
            ->where('path', $path)
            ->when($mimeTypes !== [], fn ($query) => $query->whereIn('mime_type', $mimeTypes))
            ->exists();
    }

    /**
     * @return array<int, TextInput|Hidden>
     */
    private static function searchAndPageFields(): array
    {
        return [
            TextInput::make('search')
                ->hiddenLabel()
                ->placeholder('Tìm theo tên file hoặc mô tả...')
                ->live(debounce: 400)
                ->afterStateUpdated(fn (Set $set) => $set('page', 1)),
            Hidden::make('page')->default(1),
        ];
    }

    private static function baseAction(): Action
    {
        return Action::make('chooseFromLibrary')
            ->label('Chọn từ thư viện')
            ->icon(Heroicon::OutlinedPhoto)
            ->color('gray')
            ->visible(fn (): bool => Gate::allows('viewAny', Media::class))
            ->modalWidth('2xl')
            ->modalHeading('Chọn ảnh từ thư viện');
    }

    private static function chooseOneFromLibraryAction(FileUpload $fileUpload): Action
    {
        return self::baseAction()
            ->modalSubmitActionLabel('Dùng ảnh này')
            ->schema([
                ...self::searchAndPageFields(),
                MediaGridPicker::make('path')
                    ->disk(fn () => $fileUpload->getDiskName())
                    ->acceptedMimeTypes(fn () => $fileUpload->getAcceptedFileTypes() ?? [])
                    ->required()
                    ->validationMessages(['required' => 'Vui lòng chọn một ảnh.']),
            ])
            ->action(function (array $data) use ($fileUpload): void {
                $fileUpload->state($data['path']);
            });
    }

    private static function chooseMultipleFromLibraryAction(FileUpload $fileUpload, string $submitLabel): Action
    {
        return self::baseAction()
            ->modalSubmitActionLabel($submitLabel)
            ->schema([
                ...self::searchAndPageFields(),
                MediaGridPicker::make('paths')
                    ->multiple()
                    ->default([])
                    ->disk(fn () => $fileUpload->getDiskName())
                    ->acceptedMimeTypes(fn () => $fileUpload->getAcceptedFileTypes() ?? []),
            ])
            ->action(function (array $data) use ($fileUpload): void {
                $current = Arr::wrap($fileUpload->getState() ?? []);
                $selected = $data['paths'] ?? [];
                $newPaths = array_values(array_diff($selected, $current));
                $skipped = count($selected) - count($newPaths);
                $maxFiles = $fileUpload->getMaxFiles();

                if ($maxFiles !== null && (count($current) + count($newPaths)) > $maxFiles) {
                    Notification::make()
                        ->title("Chỉ được tối đa {$maxFiles} ảnh, không thể thêm ảnh đã chọn.")
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                $fileUpload->state([...$current, ...$newPaths]);

                if ($skipped > 0) {
                    Notification::make()
                        ->title("Đã bỏ qua {$skipped} ảnh đã có sẵn trong danh sách.")
                        ->warning()
                        ->send();
                }
            });
    }
}
