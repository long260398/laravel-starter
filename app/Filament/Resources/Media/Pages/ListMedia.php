<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Tải lên')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->authorize('create')
                ->schema([
                    FileUpload::make('files')
                        ->label('Chọn file (jpg, png, webp, gif — tối đa 2MB/file)')
                        ->multiple()
                        ->storeFiles(false)
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->maxSize(2048)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $count = self::storeUploadedFiles($data['files']);

                    Notification::make()
                        ->title("Đã tải lên {$count} file.")
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @param  list<TemporaryUploadedFile>  $files
     */
    private static function storeUploadedFiles(array $files): int
    {
        foreach ($files as $file) {
            $dimensions = @getimagesize($file->getRealPath());
            $path = $file->store('media/'.now()->format('Y/m'), config('media.disk'));

            Media::create([
                'disk' => config('media.disk'),
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $dimensions !== false ? $dimensions[0] : null,
                'height' => $dimensions !== false ? $dimensions[1] : null,
                'uploaded_by' => Auth::id(),
            ]);
        }

        return count($files);
    }
}
