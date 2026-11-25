<?php

namespace App\Actions;

use App\Actions\Concerns\ResolvesContentImagePath;
use App\Models\Media;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Gỡ mọi thẻ `<img>` trong HTML trỏ tới đúng `$media` (xem
 * ResolvesContentImagePath cho quy tắc so khớp) — dùng khi Admin xóa cưỡng ép
 * 1 media đang được chèn trong nội dung RichEditor (Post/StaticPage/Service/
 * FAQ). Sau khi gỡ `<img>`, dọn luôn các thẻ bọc ngoài đã rỗng (vd `<p>`,
 * `<figure>`, `<a>` chỉ chứa đúng ảnh đó) để không để lại khối rỗng vô nghĩa —
 * nếu thẻ bọc còn nội dung khác (chữ, ảnh khác) thì giữ nguyên, chỉ bỏ ảnh.
 */
class RemoveMediaFromHtml
{
    use ResolvesContentImagePath;

    public function handle(string $html, Media $media): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $root = $xpath->query('//div[@id="__root"]')?->item(0);

        if (! $root instanceof DOMElement) {
            return $html;
        }

        $removed = false;

        foreach (iterator_to_array($xpath->query('.//img', $root)) as $img) {
            if ($this->resolveContentImagePath($img, $media->disk) !== $media->path) {
                continue;
            }

            $parent = $img->parentNode;
            $parent?->removeChild($img);
            $this->removeEmptyAncestors($parent, $root);
            $removed = true;
        }

        if (! $removed) {
            return $html;
        }

        $result = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    /**
     * Đi ngược lên từ `$node`, xóa từng thẻ bọc đã rỗng (không còn chữ, không
     * còn thẻ con) cho tới khi gặp thẻ còn nội dung khác hoặc chạm `$root`.
     */
    private function removeEmptyAncestors(?DOMNode $node, DOMElement $root): void
    {
        while ($node instanceof DOMElement && $node !== $root) {
            if (trim($node->textContent) !== '' || $this->hasElementChild($node)) {
                return;
            }

            $parent = $node->parentNode;
            $parent?->removeChild($node);
            $node = $parent;
        }
    }

    private function hasElementChild(DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return true;
            }
        }

        return false;
    }
}
