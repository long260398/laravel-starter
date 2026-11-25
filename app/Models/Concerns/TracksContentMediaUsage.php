<?php

namespace App\Models\Concerns;

use App\Actions\ReleaseMediaUsage;
use App\Actions\RemoveMediaFromHtml;
use App\Actions\SyncContentMediaUsage;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Theo dõi usage của ảnh chèn TRONG nội dung HTML của 1 field RichEditor —
 * khác TracksMediaUsage (1 field = 1 path cố định): ở đây 1 field có thể
 * chứa NHIỀU ảnh khác nhau, nên phải so khớp (diff) danh sách ảnh hiện có
 * trong HTML với usage đã ghi cho field đó mỗi lần field thay đổi, xem
 * {@see SyncContentMediaUsage}.
 *
 * Model dùng trait này phải khai báo:
 * protected string $contentMediaColumn = 'content'; (hoặc tên field khác, ví dụ 'answer' của Faq)
 *
 * Quy tắc soft delete giống TracksMediaUsage: model có SoftDeletes chỉ gỡ hết
 * usage khi forceDelete(); không có SoftDeletes thì gỡ ngay ở event `deleted`.
 */
trait TracksContentMediaUsage
{
    protected static function bootTracksContentMediaUsage(): void
    {
        static::saved(function (Model $model): void {
            $column = $model->contentMediaColumn;

            // `wasChanged()` không đáng tin cậy ngay khi vừa tạo — Eloquent chỉ
            // gọi syncChanges() ở performUpdate(), không gọi ở performInsert()
            // (xem Illuminate\Database\Eloquent\Model), nên lúc `saved` vừa bắn
            // cho 1 record mới tạo, wasChanged() luôn trả về false dù field có
            // giá trị. Phải tự kiểm tra `wasRecentlyCreated` cho nhánh tạo mới.
            if (! $model->wasRecentlyCreated && ! $model->wasChanged($column)) {
                return;
            }

            app(SyncContentMediaUsage::class)->handle(
                $model,
                $column,
                (string) $model->getAttribute($column),
                config('media.disk'),
            );
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::forceDeleted(function (Model $model): void {
                app(SyncContentMediaUsage::class)->handle($model, $model->contentMediaColumn, '', config('media.disk'));
            });
        } else {
            static::deleted(function (Model $model): void {
                app(SyncContentMediaUsage::class)->handle($model, $model->contentMediaColumn, '', config('media.disk'));
            });
        }
    }

    /**
     * `$contentMediaColumn` là `protected` — truy cập từ ngoài model (ví dụ
     * lệnh `media:sync`) phải qua getter này (xem lý do ở TracksMediaUsage::getFileColumns()).
     */
    public function getContentMediaColumn(): string
    {
        return $this->contentMediaColumn;
    }

    /**
     * Gỡ đúng 1 ảnh (`$media`) khỏi nội dung field này rồi lưu lại — dùng khi
     * Admin xóa cưỡng ép ảnh đang được chèn trong RichEditor.
     *
     * Lưu bằng `saveQuietly()` (bỏ qua mọi event) thay vì `save()` thường:
     * đã biết chính xác ảnh nào cần gỡ nên tự gọi ReleaseMediaUsage trực tiếp,
     * không cần hook `saved` ở trên quét lại toàn bộ HTML. Quan trọng hơn,
     * `save()` thường sẽ bắn lại mọi hook `saving`/`updating` khác của model —
     * vd Post có hook tự set `published_at = now()` khi status Published mà
     * published_at đang null; việc gỡ 1 ảnh trong content không được phép gây
     * tác dụng phụ đó (hoặc bất kỳ observer nào khác gắn vào event save).
     */
    protected function removeMediaFromContentField(string $column, Media $media): void
    {
        $html = (string) $this->getAttribute($column);
        $updated = app(RemoveMediaFromHtml::class)->handle($html, $media);

        if ($updated === $html) {
            return;
        }

        $this->{$column} = $updated;
        $this->saveQuietly();

        app(ReleaseMediaUsage::class)->handle($media->disk, $media->path, $this, $column);
    }
}
