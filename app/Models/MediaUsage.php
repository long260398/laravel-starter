<?php

namespace App\Models;

use App\Contracts\HasMediaUsageLabel;
use Database\Factories\MediaUsageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Một nơi đang dùng một {@see Media}. `usable` là model bất kỳ implement
 * {@see HasMediaUsageLabel} (Post, Banner, GalleryImage, Setting...) — bảng
 * này không cần biết tên model/resource cụ thể nào.
 */
#[Fillable(['media_id', 'usable_type', 'usable_id', 'field'])]
class MediaUsage extends Model
{
    /** @use HasFactory<MediaUsageFactory> */
    use HasFactory;

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }

    public function label(): string
    {
        $usable = $this->usable;

        if ($usable instanceof HasMediaUsageLabel) {
            return $usable->mediaUsageLabel();
        }

        return class_basename($this->usable_type).' #'.$this->usable_id;
    }

    public function url(): ?string
    {
        $usable = $this->usable;

        if ($usable instanceof HasMediaUsageLabel) {
            return $usable->mediaUsageUrl();
        }

        return null;
    }
}
