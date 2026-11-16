<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Tests\TestCase;

class GalleryReservedSlugTest extends TestCase
{
    public function test_gallery_prefix_is_reserved_when_module_enabled(): void
    {
        config(['modules.gallery.enabled' => true]);

        $this->assertTrue(StaticPage::isReservedSlug('gallery'));
    }

    public function test_gallery_prefix_is_reserved_even_when_module_disabled(): void
    {
        config(['modules.gallery.enabled' => false]);

        $this->assertTrue(StaticPage::isReservedSlug('gallery'));
    }
}
