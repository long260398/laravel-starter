<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Xóa file lưu trên disk khi cột file bị thay đổi (update) hoặc khi record bị xóa vĩnh viễn (forceDelete).
 * Soft delete không xóa file vì record còn có thể được restore.
 *
 * Model dùng trait này phải khai báo:
 * protected array $fileColumns = ['column_name' => 'disk_name'];
 */
trait DeletesStoredFiles
{
    /**
     * @var array<string, array{disk: string, path: string}>
     */
    protected array $pendingFileCleanup = [];

    protected static function bootDeletesStoredFiles(): void
    {
        static::updating(function (Model $model): void {
            $model->pendingFileCleanup = [];

            foreach ($model->fileColumns as $column => $disk) {
                if (! $model->isDirty($column)) {
                    continue;
                }

                $original = $model->getOriginal($column);

                if (filled($original)) {
                    $model->pendingFileCleanup[$column] = ['disk' => $disk, 'path' => $original];
                }
            }
        });

        static::updated(function (Model $model): void {
            foreach ($model->pendingFileCleanup as $info) {
                Storage::disk($info['disk'])->delete($info['path']);
            }

            $model->pendingFileCleanup = [];
        });

        static::forceDeleted(function (Model $model): void {
            foreach ($model->fileColumns as $column => $disk) {
                $path = $model->getAttribute($column);

                if (filled($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }
        });
    }
}
