<?php

namespace Tests\Feature;

use App\Modules\Services\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_the_module_at_runtime_does_not_delete_existing_data(): void
    {
        $service = Service::factory()->published()->create();

        config(['modules.services.enabled' => false]);

        $this->assertDatabaseHas('services', ['id' => $service->id]);
        $this->assertNotNull(Service::query()->find($service->id));
    }
}
