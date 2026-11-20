<?php

namespace App\Models;

use App\Contracts\HasMediaUsageLabel;
use App\Enums\PublishStatus;
use App\Models\Concerns\TracksMediaUsage;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'slug', 'featured_image', 'content', 'status', 'published_at'])]
class Post extends Model implements HasMediaUsageLabel
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes, TracksMediaUsage;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'featured_image' => 'public',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->status === PublishStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * HTML của `content` đã được lọc an toàn — dùng giá trị này khi render ra frontend,
     * không render trực tiếp `content` thô (yêu cầu bắt buộc trong docs/STANDARDS.md).
     */
    protected function safeContent(): Attribute
    {
        return Attribute::get(fn (): string => (string) str((string) $this->content)->sanitizeHtml());
    }

    /**
     * Bài đã xuất bản và đã tới thời điểm `published_at`. Dùng để lấy bài cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PublishStatus::Published)
            ->where('published_at', '<=', now());
    }
}
