<?php

namespace App\Modules\Gallery\Models;

use App\Models\Concerns\DeletesStoredFiles;
use App\Modules\Gallery\Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Không dùng SoftDeletes: ảnh không cần "thùng rác" riêng (chỉ Album mới soft
 * delete). delete() ở đây đã là xóa vĩnh viễn duy nhất, nên DeletesStoredFiles
 * dọn file ngay ở event `deleted` (xem App\Models\Concerns\DeletesStoredFiles).
 */
#[Fillable(['gallery_album_id', 'image', 'caption', 'sort_order'])]
class GalleryImage extends Model
{
    /** @use HasFactory<GalleryImageFactory> */
    use DeletesStoredFiles, HasFactory;

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
}
