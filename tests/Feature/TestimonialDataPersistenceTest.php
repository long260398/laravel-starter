<?php

namespace Tests\Feature;

use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialDataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_the_module_at_runtime_does_not_delete_existing_data(): void
    {
        $testimonial = Testimonial::factory()->published()->create();

        config(['modules.testimonials.enabled' => false]);

        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id]);
        $this->assertNotNull(Testimonial::query()->find($testimonial->id));
    }
}
