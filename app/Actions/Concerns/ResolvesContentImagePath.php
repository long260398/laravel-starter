<?php

namespace App\Actions\Concerns;

use DOMElement;
use Illuminate\Support\Facades\Storage;

/**
 * Suy ra path (tương đối trên `$disk`) của 1 thẻ `<img>` trong content
 * RichEditor — dùng chung giữa ExtractContentMediaPaths và RemoveMediaFromHtml
 * để quy tắc so khớp luôn nhất quán ở cả 2 chiều (quét / gỡ).
 *
 * Ưu tiên `data-id`: Filament RichEditor ghi ĐÚNG path tương đối vào đó khi
 * ảnh được tải lên qua nút upload — xem
 * vendor/filament/forms/src/Components/RichEditor.php::resolveFileAttachmentIds(),
 * nơi `$node->attrs->id` bị thay bằng path thật ngay sau khi file được lưu.
 * Khớp tuyệt đối theo path, không phải suy ra từ URL nên không bị ảnh hưởng
 * bởi APP_URL tuyệt đối/tương đối.
 *
 * `src` chỉ dùng làm phương án dự phòng cho ảnh không có `data-id` (HTML dán
 * tay, nội dung fake trong test, hoặc ảnh cũ) — so khớp theo TIỀN TỐ URL đầy
 * đủ của disk (`Storage::disk($disk)->url('')`) rồi lấy phần path còn lại,
 * không bao giờ so khớp kiểu "chứa chuỗi con" (vd path "posts/a.jpg" không
 * được khớp với "posts/aa.jpg" hay "x/posts/a.jpg").
 */
trait ResolvesContentImagePath
{
    protected function resolveContentImagePath(DOMElement $img, string $disk): ?string
    {
        $dataId = trim($img->getAttribute('data-id'));

        if ($dataId !== '') {
            return urldecode($dataId);
        }

        $src = $img->getAttribute('src');

        if ($src === '') {
            return null;
        }

        $prefix = rtrim(Storage::disk($disk)->url(''), '/').'/';

        if (! str_starts_with($src, $prefix)) {
            return null;
        }

        return urldecode(substr($src, strlen($prefix)));
    }
}
