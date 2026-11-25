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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'slug', 'category_id', 'featured_image', 'excerpt', 'content', 'status', 'published_at'])]
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
     * Tóm tắt dùng để hiển thị (card, meta description). Ưu tiên `excerpt` do
     * admin nhập; bài cũ chưa có excerpt tự trích từ `safe_content`.
     */
    protected function excerptForDisplay(): Attribute
    {
        return Attribute::get(fn (): string => $this->excerpt !== null && $this->excerpt !== ''
            ? $this->excerpt
            : (string) str($this->safe_content)->stripTags()->squish()->limit(160));
    }

    /**
     * Thời gian đọc ước tính (phút), dựa trên số từ trong `safe_content` / 200 wpm.
     */
    protected function readingTimeMinutes(): Attribute
    {
        return Attribute::get(function (): int {
            $plainText = trim((string) str($this->safe_content)->stripTags());
            $wordCount = $plainText === '' ? 0 : count(preg_split('/\s+/u', $plainText));

            return max(1, (int) ceil($wordCount / 200));
        });
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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
