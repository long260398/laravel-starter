<?php

namespace App\Actions;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class BuildPostTableOfContents
{
    /**
     * Sinh mục lục từ các heading `h2`/`h3` trong nội dung ĐÃ sanitize
     * (`$post->safe_content`), gắn id an toàn (tiền tố `muc-`, slug hoá,
     * không trùng) lên từng heading. Input đã qua `sanitizeHtml()` trước khi
     * vào Action này nên không có rủi ro XSS mới — chỉ thêm/thay attribute
     * `id`, không giữ lại thay đổi cấu trúc nào khác. Id luôn được gắn lên
     * mọi heading (phục vụ liên kết neo từ nơi khác); `items` (dùng để hiện
     * khối mục lục) chỉ trả về khi có từ 3 heading trở lên.
     *
     * @return array{html: string, items: array<int, array{level: int, text: string, id: string}>}
     */
    public function handle(string $safeHtml): array
    {
        if (trim($safeHtml) === '') {
            return ['html' => $safeHtml, 'items' => []];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        // Khai báo encoding qua PI giả để DOMDocument không vỡ tiếng Việt có dấu
        // (lỗi kinh điển của DOMDocument::loadHTML với input non-ASCII).
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__toc_root">'.$safeHtml.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $root = $xpath->query('//div[@id="__toc_root"]')?->item(0);

        if ($root === null) {
            return ['html' => $safeHtml, 'items' => []];
        }

        $usedIds = [];
        $items = [];

        foreach ($xpath->query('.//h2 | .//h3', $root) as $heading) {
            $text = trim($heading->textContent);

            if ($text === '') {
                continue;
            }

            $id = $this->uniqueId($text, $usedIds);
            $heading->setAttribute('id', $id);

            $items[] = [
                'level' => (int) substr($heading->nodeName, 1),
                'text' => $text,
                'id' => $id,
            ];
        }

        $html = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $html .= $document->saveHTML($child);
        }

        return ['html' => $html, 'items' => count($items) >= 3 ? $items : []];
    }

    /**
     * @param  array<string, true>  $usedIds
     */
    private function uniqueId(string $text, array &$usedIds): string
    {
        $base = 'muc-'.Str::slug($text);
        $id = $base;
        $suffix = 2;

        while (isset($usedIds[$id])) {
            $id = "{$base}-{$suffix}";
            $suffix++;
        }

        $usedIds[$id] = true;

        return $id;
    }
}
