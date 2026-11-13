<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\DeletesStoredFiles;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Khi render ở Blade frontend: nếu `open_in_new_tab` là true, link phải có
 * `rel="noopener noreferrer"` để chống tabnabbing (chưa làm, dành cho phần frontend sau này).
 */
#[Fillable([
    'title', 'subtitle', 'image', 'image_mobile', 'link_url', 'open_in_new_tab',
    'button_text', 'position', 'starts_at', 'ends_at', 'status',
])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use DeletesStoredFiles, HasFactory, SoftDeletes;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'image' => 'public',
        'image_mobile' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (Banner $banner): void {
            $banner->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'open_in_new_tab' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Banner đã xuất bản, đang trong khoảng thời gian hiển thị (null = không giới hạn)
     * và đúng vị trí `$position`, sắp theo `sort_order`. Dùng để lấy banner cho frontend.
     */
    public function scopeVisibleAt(Builder $query, string $position): Builder
    {
        $now = now();

        return $query
            ->where('position', $position)
            ->where('status', PublishStatus::Published)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order');
    }

    /**
     * True nếu `$position` nằm trong danh sách vị trí khai báo ở `config('banners.positions')`.
     */
    public static function isValidPosition(string $position): bool
    {
        return array_key_exists($position, config('banners.positions', []));
    }

    /**
     * True nếu `$url` là đường dẫn nội bộ an toàn (bắt đầu bằng "/", không phải
     * "//" hay "/\" để tránh protocol-relative URL hoặc backslash-trick) hoặc là
     * URL http/https có host hợp lệ. Dùng để chống XSS qua scheme javascript:/data:/vbscript:.
     */
    public static function isSafeLinkUrl(string $url): bool
    {
        $value = trim($url);

        if ($value === '') {
            return false;
        }

        if (preg_match('/[\s\x00-\x1F\x7F]/', $value) === 1) {
            return false;
        }

        if (str_starts_with($value, '//') || str_starts_with($value, '/\\')) {
            return false;
        }

        if (str_starts_with($value, '/')) {
            return true;
        }

        $parts = parse_url($value);

        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return filled($parts['host'] ?? null);
    }
}
