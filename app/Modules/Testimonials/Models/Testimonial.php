<?php

namespace App\Modules\Testimonials\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\DeletesStoredFiles;
use App\Modules\Testimonials\Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_name', 'customer_title', 'avatar', 'content', 'rating', 'status', 'sort_order',
])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use DeletesStoredFiles, HasFactory, SoftDeletes;

    /**
     * @var array<string, string>
     */
    protected array $fileColumns = [
        'avatar' => 'public',
    ];

    protected static function booted(): void
    {
        static::creating(function (Testimonial $testimonial): void {
            $testimonial->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): TestimonialFactory
    {
        return TestimonialFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'rating' => 'integer',
        ];
    }

    /**
     * Cảm nhận đã xuất bản. Dùng để lấy cảm nhận cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }
}
