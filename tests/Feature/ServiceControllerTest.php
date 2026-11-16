<?php

namespace Tests\Feature;

use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_200_and_lists_published_services(): void
    {
        $service = Service::factory()->published()->create();

        $this->get('/services')->assertStatus(200)->assertSee($service->name);
    }

    public function test_index_does_not_list_draft_services(): void
    {
        $service = Service::factory()->create();

        $this->get('/services')->assertDontSee($service->name);
    }

    public function test_index_filters_by_category(): void
    {
        $categoryA = ServiceCategory::factory()->published()->create();
        $categoryB = ServiceCategory::factory()->published()->create();

        $serviceA = Service::factory()->published()->create(['service_category_id' => $categoryA->id]);
        $serviceB = Service::factory()->published()->create(['service_category_id' => $categoryB->id]);

        $response = $this->get("/services?category={$categoryA->slug}");

        $response->assertSee($serviceA->name)->assertDontSee($serviceB->name);
    }

    public function test_show_returns_200_for_published_service(): void
    {
        $service = Service::factory()->published()->create();

        $this->get("/services/{$service->slug}")->assertStatus(200)->assertSee($service->name);
    }

    public function test_show_returns_404_for_draft_service(): void
    {
        $service = Service::factory()->create();

        $this->get("/services/{$service->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/services/khong-ton-tai')->assertStatus(404);
    }

    public function test_services_route_is_not_shadowed_by_static_page_fallback(): void
    {
        $service = Service::factory()->published()->create();

        $this->get('/services')->assertSee($service->name);
    }
}
