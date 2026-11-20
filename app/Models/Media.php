<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một dòng = một file vật lý trên disk (kho media dùng chung kiểu WordPress).
 * `usages` liệt kê mọi nơi file này đang được dùng — xem {@see MediaUsage}.
 */
#[Fillable(['disk', 'path', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt', 'uploaded_by'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return HasMany<MediaUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isUsed(): bool
    {
        return $this->usages()->exists();
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): ?string => public_file_url($this->path, $this->disk));
    }
}
