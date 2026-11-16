<?php

namespace App\Modules\Services\Models;

use App\Enums\PublishStatus;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'sort_order', 'status'])]
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (ServiceCategory $category): void {
            $category->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): ServiceCategoryFactory
    {
        return ServiceCategoryFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
        ];
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Danh mục đã xuất bản, sắp theo `sort_order`. Dùng để lấy danh mục cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published)->orderBy('sort_order');
    }
}
