<?php

namespace Tests\Feature;

use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_published_testimonials_section(): void
    {
        $testimonial = Testimonial::factory()->published()->create();

        $response = $this->get('/');

        $response->assertSee('Khách hàng nói gì');
        $response->assertSee($testimonial->customer_name);
    }

    public function test_home_page_hides_section_when_no_published_testimonials(): void
    {
        Testimonial::factory()->create(); // draft

        $this->get('/')->assertDontSee('Khách hàng nói gì');
    }

    public function test_draft_testimonial_does_not_appear_on_home_page(): void
    {
        $draft = Testimonial::factory()->create();
        Testimonial::factory()->published()->create();

        $this->get('/')->assertDontSee($draft->customer_name);
    }

    public function test_testimonial_content_is_escaped_on_home_page(): void
    {
        $testimonial = Testimonial::factory()->published()->create([
            'content' => '<script>alert(1)</script>',
        ]);

        $response = $this->get('/');

        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
