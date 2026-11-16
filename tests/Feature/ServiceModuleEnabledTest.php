<?php

namespace Tests\Feature;

use App\Modules\Services\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_featured_services_section(): void
    {
        $service = Service::factory()->published()->featured()->create();

        $response = $this->get('/');

        $response->assertSee('Dịch vụ nổi bật');
        $response->assertSee($service->name);
    }

    public function test_home_page_hides_section_when_no_featured_services(): void
    {
        Service::factory()->published()->create(); // không featured

        $this->get('/')->assertDontSee('Dịch vụ nổi bật');
    }

    public function test_header_has_services_nav_link(): void
    {
        $this->get('/')->assertSee('Dịch vụ')->assertSee(route('services.index'), false);
    }

    public function test_sitemap_includes_published_service(): void
    {
        $service = Service::factory()->published()->create();

        $this->get('/sitemap.xml')->assertSee(route('services.show', $service->slug), false);
    }

    public function test_sitemap_excludes_draft_service(): void
    {
        $service = Service::factory()->create();

        $this->get('/sitemap.xml')->assertDontSee(route('services.show', $service->slug), false);
    }
}
