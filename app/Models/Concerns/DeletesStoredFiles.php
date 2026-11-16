<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Xóa file lưu trên disk khi cột file bị thay đổi (update), hoặc khi record bị xóa.
 *
 * - Model có SoftDeletes: chỉ xóa file khi forceDelete() (xóa vĩnh viễn) — soft
 *   delete thường giữ file vì record còn có thể được restore.
 * - Model không có SoftDeletes: delete() đã là xóa vĩnh viễn duy nhất, nên dọn
 *   file ngay ở event `deleted` (model không có forceDelete() để mà hook vào).
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

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::forceDeleted(function (Model $model): void {
                static::deleteFileColumns($model);
            });
        } else {
            static::deleted(function (Model $model): void {
                static::deleteFileColumns($model);
            });
        }
    }

    private static function deleteFileColumns(Model $model): void
    {
        foreach ($model->fileColumns as $column => $disk) {
            $path = $model->getAttribute($column);

            if (filled($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
