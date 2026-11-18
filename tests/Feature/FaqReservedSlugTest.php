<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Tests\TestCase;

class FaqReservedSlugTest extends TestCase
{
    public function test_faq_prefix_is_reserved_when_module_enabled(): void
    {
        config(['modules.faq.enabled' => true]);

        $this->assertTrue(StaticPage::isReservedSlug('faq'));
    }

    public function test_faq_prefix_is_reserved_even_when_module_disabled(): void
    {
        config(['modules.faq.enabled' => false]);

        $this->assertTrue(StaticPage::isReservedSlug('faq'));
    }
}
