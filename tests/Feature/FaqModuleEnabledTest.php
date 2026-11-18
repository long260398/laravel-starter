<?php

namespace Tests\Feature;

use App\Modules\Faq\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_has_faq_nav_link(): void
    {
        $this->get('/')->assertSee('Hỏi đáp')->assertSee(route('faq.index'), false);
    }

    public function test_sitemap_includes_faq_page(): void
    {
        Faq::factory()->published()->create();

        $this->get('/sitemap.xml')->assertSee(route('faq.index'), false);
    }
}
