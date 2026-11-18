<?php

namespace Tests\Feature;

use App\Modules\Faq\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_200_and_lists_published_faqs(): void
    {
        $faq = Faq::factory()->published()->create();

        $this->get('/faq')->assertStatus(200)->assertSee($faq->question);
    }

    public function test_index_does_not_list_draft_faqs(): void
    {
        $faq = Faq::factory()->create();

        $this->get('/faq')->assertDontSee($faq->question);
    }

    public function test_answer_html_is_sanitized(): void
    {
        $faq = Faq::factory()->published()->create([
            'answer' => '<script>alert(1)</script><p>Nội dung an toàn</p>',
        ]);

        $response = $this->get('/faq');

        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('Nội dung an toàn');
    }
}
