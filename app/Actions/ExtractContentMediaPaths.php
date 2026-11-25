<?php

namespace App\Actions;

use App\Actions\Concerns\ResolvesContentImagePath;
use DOMDocument;
use DOMXPath;

/**
 * Quét mọi thẻ `<img>` trong HTML của 1 field RichEditor, trả về path của
 * những ảnh thực sự được lưu trên `$disk` đó — xem ResolvesContentImagePath
 * cho quy tắc so khớp. Ảnh link ngoài (không có `data-id` và `src` không khớp
 * tiền tố URL của disk) bị bỏ qua vì không phải file trong kho media.
 */
class ExtractContentMediaPaths
{
    use ResolvesContentImagePath;

    /**
     * @return array<int, string>
     */
    public function handle(string $html, string $disk): array
    {
        if (trim($html) === '') {
            return [];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        // Khai báo encoding qua PI giả để DOMDocument không vỡ tiếng Việt có dấu
        // (lỗi kinh điển của DOMDocument::loadHTML với input non-ASCII).
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $paths = [];

        foreach ($xpath->query('//img') as $img) {
            $path = $this->resolveContentImagePath($img, $disk);

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}
