<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Tests\TestCase;

class ReservedModuleSlugTest extends TestCase
{
    public function test_services_prefix_is_reserved_when_module_enabled(): void
    {
        config(['modules.services.enabled' => true]);

        $this->assertTrue(StaticPage::isReservedSlug('services'));
    }

    public function test_services_prefix_is_reserved_even_when_module_disabled(): void
    {
        config(['modules.services.enabled' => false]);

        $this->assertTrue(StaticPage::isReservedSlug('services'));
    }
}
