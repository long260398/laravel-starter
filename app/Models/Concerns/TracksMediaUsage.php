<?php

namespace App\Models\Concerns;

use App\Actions\RegisterMediaUsage;
use App\Actions\ReleaseMediaUsage;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Đăng ký/gỡ usage trong kho media (xem docs/STANDARDS.md mục "Lưu trữ file")
 * khi cột file của model thay đổi hoặc model bị xóa hẳn. KHÔNG xóa file trên
 * disk — file chỉ bị xóa từ trang Thư viện media khi không còn usage nào.
 *
 * - Model có SoftDeletes: chỉ gỡ usage khi forceDelete() (xóa vĩnh viễn) —
 *   soft delete thường giữ bản ghi có thể restore nên vẫn tính là đang dùng.
 * - Model không có SoftDeletes: delete() đã là xóa vĩnh viễn duy nhất, nên
 *   gỡ usage ngay ở event `deleted`.
 *
 * Model dùng trait này phải khai báo:
 * protected array $fileColumns = ['column_name' => 'disk_name'];
 *
 * Mặc định {@see mediaUsageLabel()}/{@see mediaUsageUrl()} dò resource Filament
 * quản lý model qua `Filament::getModelResource()` — model nào không có trang
 * sửa riêng (ví dụ GalleryImage trỏ về album cha) tự override 2 hàm này.
 */
trait TracksMediaUsage
{
    /**
     * @var array<string, array{disk: string, path: string}>
     */
    protected array $pendingMediaRelease = [];

    protected static function bootTracksMediaUsage(): void
    {
        static::created(function (Model $model): void {
            foreach ($model->fileColumns as $column => $disk) {
                $path = $model->getAttribute($column);

                if (filled($path)) {
                    app(RegisterMediaUsage::class)->handle($disk, $path, $model, $column);
                }
            }
        });

        static::updating(function (Model $model): void {
            $model->pendingMediaRelease = [];

            foreach ($model->fileColumns as $column => $disk) {
                if (! $model->isDirty($column)) {
                    continue;
                }

                $original = $model->getOriginal($column);

                if (filled($original)) {
                    $model->pendingMediaRelease[$column] = ['disk' => $disk, 'path' => $original];
                }
            }
        });

        static::updated(function (Model $model): void {
            foreach ($model->fileColumns as $column => $disk) {
                if (! $model->wasChanged($column)) {
                    continue;
                }

                $path = $model->getAttribute($column);

                if (filled($path)) {
                    app(RegisterMediaUsage::class)->handle($disk, $path, $model, $column);
                }
            }

            foreach ($model->pendingMediaRelease as $column => $info) {
                app(ReleaseMediaUsage::class)->handle($info['disk'], $info['path'], $model, $column);
            }

            $model->pendingMediaRelease = [];
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::forceDeleted(function (Model $model): void {
                static::releaseFileColumnsUsage($model);
            });
        } else {
            static::deleted(function (Model $model): void {
                static::releaseFileColumnsUsage($model);
            });
        }
    }

    private static function releaseFileColumnsUsage(Model $model): void
    {
        foreach ($model->fileColumns as $column => $disk) {
            $path = $model->getAttribute($column);

            if (filled($path)) {
                app(ReleaseMediaUsage::class)->handle($disk, $path, $model, $column);
            }
        }
    }

    /**
     * `$fileColumns` là `protected` — truy cập từ ngoài model (ví dụ lệnh
     * `media:sync`) phải qua getter này, vì truy cập trực tiếp thuộc tính
     * `protected` từ ngoài sẽ bị `Model::__get()` chặn lại và luôn trả về
     * null (Eloquent coi đó là tên attribute, không phải thuộc tính PHP).
     *
     * @return array<string, string>
     */
    public function getFileColumns(): array
    {
        return $this->fileColumns;
    }

    public function mediaUsageLabel(): string
    {
        $resource = Filament::getModelResource(static::class);

        if ($resource === null) {
            return class_basename(static::class).' #'.$this->getKey();
        }

        return $resource::getModelLabel().': '.$resource::getRecordTitle($this);
    }

    public function mediaUsageUrl(): ?string
    {
        $resource = Filament::getModelResource(static::class);

        if ($resource === null) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $this]);
    }
}
