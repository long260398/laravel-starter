<?php

namespace App\Modules\Faq\Actions;

use App\Modules\Faq\Models\Faq;
use Illuminate\Support\Collection;

class BuildFaqSchema
{
    /**
     * Dựng mảng schema.org FAQPage từ các FAQ đã xuất bản. Trả về mảng PHP
     * thuần — nơi gọi (view) chỉ `@json(...)` nó, không tự nối chuỗi JSON, để
     * Blade tự thoát ký tự an toàn khi nhúng vào <script type="application/ld+json">.
     * Answer dùng `safe_content` (đã sanitizeHtml()), không dùng `answer` thô.
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>
     */
    public function handle(Collection $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->safe_content,
                ],
            ])->values()->all(),
        ];
    }
}
