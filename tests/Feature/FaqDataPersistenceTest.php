<?php

namespace Tests\Feature;

use App\Modules\Faq\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqDataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_the_module_at_runtime_does_not_delete_existing_data(): void
    {
        $faq = Faq::factory()->published()->create();

        config(['modules.faq.enabled' => false]);

        $this->assertDatabaseHas('faqs', ['id' => $faq->id]);
        $this->assertNotNull(Faq::query()->find($faq->id));
    }
}
