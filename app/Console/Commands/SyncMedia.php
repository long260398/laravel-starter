<?php

namespace App\Console\Commands;

use App\Actions\RegisterMedia;
use App\Actions\RegisterMediaUsage;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Quét toàn bộ dữ liệu + file trên disk để đồng bộ kho media — chạy lại
 * nhiều lần an toàn (mọi đăng ký đều idempotent theo unique constraint).
 * Dùng khi mới thêm Media Library vào dự án đã có dữ liệu, hoặc nghi ngờ
 * kho media bị thiếu/lệch so với dữ liệu thật.
 */
#[Signature('media:sync')]
#[Description('Đồng bộ kho media với dữ liệu model có $fileColumns, key file của Settings, và file trên disk.')]
class SyncMedia extends Command
{
    /**
     * @var array<int, string>
     */
    private const array ALLOWED_DISK_SCAN_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        // favicon (MediaPicker của Settings chấp nhận cả 2 — hệ điều hành/thư
        // viện fileinfo không đồng nhất mime trả về cho .ico):
        'image/vnd.microsoft.icon', 'image/x-icon',
    ];

    public function handle(): int
    {
        $mediaCountBefore = Media::count();
        $usageCountBefore = MediaUsage::count();

        $this->syncFromModels();
        $this->syncFromSettings();
        $skipped = $this->syncUnknownDiskFiles();
        $missing = $this->findMediaMissingOnDisk();

        $this->info('Media mới đăng ký: '.(Media::count() - $mediaCountBefore));
        $this->info('Usage mới gắn: '.(MediaUsage::count() - $usageCountBefore));
        $this->info('File bị bỏ qua khi quét disk (ẩn/livewire-tmp/mime không cho phép): '.$skipped);

        if ($missing->isEmpty()) {
            $this->info('Không có file nào bị mất trên disk.');
        } else {
            $this->warn('File có trong DB nhưng không còn trên disk ('.$missing->count().'):');
            foreach ($missing as $path) {
                $this->line(" - {$path}");
            }
        }

        return self::SUCCESS;
    }

    private function syncFromModels(): void
    {
        foreach ($this->modelClassesUsingTracksMediaUsage() as $class) {
            /** @var Model $instance */
            $instance = new $class;

            if (! Schema::hasTable($instance->getTable())) {
                continue;
            }

            $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
                ? $class::withTrashed()
                : $class::query();

            foreach ($query->cursor() as $record) {
                foreach ($record->getFileColumns() as $column => $disk) {
                    $path = $record->getAttribute($column);

                    if (filled($path)) {
                        app(RegisterMediaUsage::class)->handle($disk, $path, $record, $column);
                    }
                }
            }
        }
    }

    private function syncFromSettings(): void
    {
        foreach (Setting::fileKeys() as $key) {
            $setting = Setting::query()->where('key', $key)->first();

            if ($setting !== null && filled($setting->value)) {
                app(RegisterMediaUsage::class)->handle(config('media.disk'), $setting->value, $setting, $key);
            }
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function modelClassesUsingTracksMediaUsage(): array
    {
        $classes = $this->discoverClassesIn(app_path('Models'), 'App\\Models');

        foreach (config('modules', []) as $module) {
            $modelsPath = rtrim($module['path'], '/\\').'/Models';

            if (is_dir($modelsPath)) {
                $classes = [...$classes, ...$this->discoverClassesIn($modelsPath, $module['namespace'].'\\Models')];
            }
        }

        return array_values(array_filter(
            $classes,
            fn (string $class): bool => in_array(TracksMediaUsage::class, class_uses_recursive($class), true),
        ));
    }

    /**
     * @return array<int, class-string>
     */
    private function discoverClassesIn(string $path, string $namespace): array
    {
        if (! is_dir($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->map(function ($file) use ($namespace): string {
                $relativeClass = Str::of($file->getRelativePathname())
                    ->beforeLast('.php')
                    ->replace(DIRECTORY_SEPARATOR, '\\');

                return $namespace.'\\'.$relativeClass;
            })
            ->filter(fn (string $class): bool => class_exists($class))
            ->values()
            ->all();
    }

    /**
     * Quét file trên disk chưa có trong `media` → đăng ký dạng "chưa dùng".
     * Trả về số file bị bỏ qua (ẩn, livewire-tmp, hoặc mime không cho phép).
     */
    private function syncUnknownDiskFiles(): int
    {
        $disk = config('media.disk');
        $storage = Storage::disk($disk);
        $knownPaths = Media::query()->where('disk', $disk)->pluck('path')->all();
        $skipped = 0;

        foreach ($storage->allFiles() as $path) {
            if (in_array($path, $knownPaths, true)) {
                continue;
            }

            if ($this->isExcludedFromDiskScan($path) || ! in_array($storage->mimeType($path), self::ALLOWED_DISK_SCAN_MIME_TYPES, true)) {
                $skipped++;

                continue;
            }

            app(RegisterMedia::class)->handle($disk, $path);
        }

        return $skipped;
    }

    private function isExcludedFromDiskScan(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if ($segment === 'livewire-tmp' || str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, string>
     */
    private function findMediaMissingOnDisk(): Collection
    {
        return Media::query()->get()
            ->reject(fn (Media $media): bool => Storage::disk($media->disk)->exists($media->path))
            ->map(fn (Media $media): string => "{$media->disk}:{$media->path}")
            ->values();
    }
}
