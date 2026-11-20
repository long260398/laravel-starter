<?php

namespace App\Modules\Gallery\Models;

use App\Contracts\HasMediaUsageLabel;
use App\Models\Concerns\TracksMediaUsage;
use App\Modules\Gallery\Database\Factories\GalleryImageFactory;
use App\Modules\Gallery\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Không dùng SoftDeletes: ảnh không cần "thùng rác" riêng (chỉ Album mới soft
 * delete). delete() ở đây đã là xóa vĩnh viễn duy nhất, nên TracksMediaUsage
 * gỡ usage ngay ở event `deleted` (xem App\Models\Concerns\TracksMediaUsage).
 */
#[Fillable(['gallery_album_id', 'image', 'caption', 'sort_order'])]
class GalleryImage extends Model implements HasMediaUsageLabel
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory, TracksMediaUsage;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'image' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (GalleryImage $image): void {
            $image->sort_order ??= (static::query()
                ->where('gallery_album_id', $image->gallery_album_id)
                ->max('sort_order') ?? 0) + 1;
        });
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): GalleryImageFactory
    {
        return GalleryImageFactory::new();
    }

    /**
     * @return BelongsTo<GalleryAlbum, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    /**
     * Ảnh không có trang sửa riêng (chỉ sửa qua ô "Ảnh trong album" của Album) —
     * trỏ thẳng về trang sửa album cha thay vì dùng mặc định của trait.
     */
    public function mediaUsageLabel(): string
    {
        return 'Ảnh trong album: '.($this->album?->title ?? '#'.$this->gallery_album_id);
    }

    public function mediaUsageUrl(): ?string
    {
        if ($this->gallery_album_id === null) {
            return null;
        }

        return GalleryAlbumResource::getUrl('edit', ['record' => $this->gallery_album_id]);
    }
}
