<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\DeletesStoredFiles;
use Database\Factories\StaticPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'featured_image', 'content', 'meta_title', 'meta_description', 'status'])]
class StaticPage extends Model
{
    /** @use HasFactory<StaticPageFactory> */
    use DeletesStoredFiles, HasFactory, SoftDeletes;

    /**
     * Slug tĩnh bị cấm vì trùng với các route/đường dẫn hệ thống đã biết.
     * Dùng cùng với {@see isReservedSlug()}, nơi còn kiểm tra động các route đã đăng ký.
     *
     * @var array<int, string>
     */
    public const array RESERVED_SLUGS = [
        'admin', 'login', 'logout', 'register', 'password',
        'storage', 'api', 'livewire', 'up', 'vendor', 'sanctum',
        'sitemap.xml', 'robots.txt', 'favicon.ico',
    ];

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'featured_image' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (StaticPage $page): void {
            $page->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
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
     * HTML của `content` đã được lọc an toàn — dùng giá trị này khi render ra frontend,
     * không render trực tiếp `content` thô (yêu cầu bắt buộc trong docs/STANDARDS.md).
     */
    protected function safeContent(): Attribute
    {
        return Attribute::get(fn (): string => (string) str((string) $this->content)->sanitizeHtml());
    }

    /**
     * True nếu `$slug` nằm trong danh sách cấm tĩnh, hoặc trùng segment đầu tiên
     * của một route bất kỳ đã đăng ký trong ứng dụng (admin, storage, livewire...).
     */
    public static function isReservedSlug(string $slug): bool
    {
        $slug = trim($slug, '/');

        if ($slug === '') {
            return false;
        }

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        foreach (Route::getRoutes() as $route) {
            $uri = trim($route->uri(), '/');

            if ($uri === '') {
                continue;
            }

            $firstSegment = Str::before($uri, '/');

            if (str_contains($firstSegment, '{')) {
                continue;
            }

            if ($firstSegment === $slug) {
                return true;
            }
        }

        return false;
    }
}
