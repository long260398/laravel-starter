<?php

namespace App\Modules\Services\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\DeletesStoredFiles;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'service_category_id', 'name', 'slug', 'short_description', 'content', 'image',
    'price', 'price_note', 'duration', 'is_featured', 'status', 'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use DeletesStoredFiles, HasFactory, SoftDeletes;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'image' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (Service $service): void {
            $service->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'is_featured' => 'boolean',
            'price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
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
     * Giá hiển thị: null → "Liên hệ". `price_note` bắt đầu bằng "/" được nối thẳng
     * sau giá (vd "1.200.000đ/buổi"), ngược lại đặt trước giá (vd "Từ 1.200.000đ").
     */
    protected function priceLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->price === null) {
                return 'Liên hệ';
            }

            $formatted = number_format((float) $this->price, 0, ',', '.').'đ';

            if (blank($this->price_note)) {
                return $formatted;
            }

            if (str_starts_with($this->price_note, '/')) {
                return $formatted.$this->price_note;
            }

            return $this->price_note.' '.$formatted;
        });
    }

    /**
     * Dịch vụ đã xuất bản. Dùng để lấy dịch vụ cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }

    /**
     * Dịch vụ nổi bật (dùng kèm {@see scopePublished()} ở phía gọi).
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
