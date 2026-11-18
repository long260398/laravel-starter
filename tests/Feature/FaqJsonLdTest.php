<?php

namespace Tests\Feature;

use App\Modules\Faq\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqJsonLdTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_ld_is_valid_faqpage_and_excludes_draft_questions(): void
    {
        Faq::factory()->published()->count(2)->create();
        $draft = Faq::factory()->create();

        $html = $this->get('/faq')->getContent();

        $schema = $this->extractJsonLd($html);

        $this->assertSame('FAQPage', $schema['@type']);
        $this->assertCount(2, $schema['mainEntity']);

        $questions = array_column($schema['mainEntity'], 'name');
        $this->assertNotContains($draft->question, $questions);
    }

    public function test_json_ld_answer_does_not_break_out_of_script_tag(): void
    {
        Faq::factory()->published()->create([
            'question' => 'Có an toàn không?',
            'answer' => '</script><script>alert(1)</script>',
        ]);

        $html = $this->get('/faq')->getContent();

        // Chỉ được có đúng 1 thẻ <script type="application/ld+json"> — câu
        // trả lời không được phép tự mở thêm thẻ <script> nào khác trong đó.
        $this->assertSame(1, substr_count($html, '<script type="application/ld+json">'));

        $schema = $this->extractJsonLd($html);

        $this->assertSame('FAQPage', $schema['@type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonLd(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertNotEmpty($matches, 'Không tìm thấy script JSON-LD trong trang /faq.');

        $decoded = json_decode($matches[1], true);

        $this->assertIsArray($decoded, 'JSON-LD không decode được: '.json_last_error_msg());

        return $decoded;
    }
}
