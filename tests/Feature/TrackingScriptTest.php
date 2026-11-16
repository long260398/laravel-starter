<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_ga_script_is_not_rendered_without_measurement_id(): void
    {
        $this->get('/')->assertDontSee('googletagmanager.com', false);
    }

    public function test_ga_script_is_rendered_with_measurement_id(): void
    {
        Setting::set('ga_measurement_id', 'G-ABC123');

        $this->get('/')->assertSee('G-ABC123', false);
    }

    public function test_fb_pixel_is_not_rendered_without_pixel_id(): void
    {
        $this->get('/')->assertDontSee('connect.facebook.net', false);
    }

    public function test_fb_pixel_is_rendered_with_pixel_id(): void
    {
        Setting::set('fb_pixel_id', '1234567890');

        $this->get('/')->assertSee('connect.facebook.net', false);
    }
}
