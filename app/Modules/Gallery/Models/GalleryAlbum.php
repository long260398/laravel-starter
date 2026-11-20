<?php

namespace App\Modules\Gallery\Models;

use App\Contracts\HasMediaUsageLabel;
use App\Enums\PublishStatus;
use App\Models\Concerns\TracksMediaUsage;
use App\Modules\Gallery\Database\Factories\GalleryAlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'slug', 'description', 'cover_image', 'status', 'sort_order'])]
class GalleryAlbum extends Model implements HasMediaUsageLabel
{
    /** @use HasFactory<GalleryAlbumFactory> */
    use HasFactory, SoftDeletes, TracksMediaUsage;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'cover_image' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (GalleryAlbum $album): void {
            $album->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });

        // Phải xóa ảnh con TRƯỚC khi hàng album bị xóa thật khỏi DB (không
        // dùng event `forceDeleted`, vì lúc đó FK restrictOnDelete trên
        // gallery_images đã chặn DELETE mất rồi). GalleryImage không có
        // SoftDeletes nên delete() của nó là xóa vĩnh viễn, tự dọn file qua
        // DeletesStoredFiles.
        static::forceDeleting(function (GalleryAlbum $album): void {
            $album->images()->get()->each(fn (GalleryImage $image) => $image->delete());
        });
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): GalleryAlbumFactory
    {
        return GalleryAlbumFactory::new();
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
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }

    /**
     * Ảnh bìa: ảnh đầu tiên theo sort_order; `cover_image` chỉ còn dùng để
     * fallback cho album tạo trước khi form bỏ ô chọn ảnh bìa riêng.
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => public_file_url($this->images->first()?->image ?? $this->cover_image)
        );
    }

    /**
     * Album đã xuất bản. Dùng để lấy album cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }
}
