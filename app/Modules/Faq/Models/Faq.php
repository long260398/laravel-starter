<?php

namespace App\Modules\Faq\Models;

use App\Contracts\HasMediaUsageLabel;
use App\Enums\PublishStatus;
use App\Models\Concerns\TracksContentMediaUsage;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Media;
use App\Modules\Faq\Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['question', 'answer', 'status', 'sort_order'])]
class Faq extends Model implements HasMediaUsageLabel
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, SoftDeletes, TracksContentMediaUsage, TracksMediaUsage;

    /**
     * Faq không có cột file cố định — chỉ có ảnh chèn trong `answer`
     * (TracksContentMediaUsage). Dùng TracksMediaUsage với $fileColumns rỗng
     * chỉ để tái dùng mediaUsageLabel()/mediaUsageUrl() mặc định của nó.
     *
     * @var array<string, string>
     */
    protected array $fileColumns = [];

    protected string $contentMediaColumn = 'answer';

    protected static function booted(): void
    {
        static::creating(function (Faq $faq): void {
            $faq->sort_order ??= (static::query()->max('sort_order') ?? 0) + 1;
        });
    }

    public function releaseMediaOnForceDelete(string $field, Media $media): ?string
    {
        $this->removeMediaFromContentField('answer', $media);

        return null;
    }

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory
     * (mặc định Laravel chỉ bỏ tiền tố "App\Models\"), phải override thủ công.
     */
    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
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
     * HTML của `answer` đã được lọc an toàn — dùng giá trị này khi render ra frontend,
     * không render trực tiếp `answer` thô (yêu cầu bắt buộc trong docs/STANDARDS.md).
     */
    protected function safeContent(): Attribute
    {
        return Attribute::get(fn (): string => (string) str((string) $this->answer)->sanitizeHtml());
    }

    /**
     * FAQ đã xuất bản. Dùng để lấy FAQ cho frontend.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }
}
