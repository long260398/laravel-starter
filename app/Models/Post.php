<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\DeletesStoredFiles;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'slug', 'featured_image', 'content', 'status', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use DeletesStoredFiles, HasFactory, SoftDeletes;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'featured_image' => 'public',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->status === PostStatus::Published && $post->published_at === null) {
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
            'status' => PostStatus::class,
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
}
