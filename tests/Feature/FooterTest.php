<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hides_social_and_contact_blocks_when_no_data(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Kết nối với chúng tôi');
        $response->assertDontSee('Giờ làm việc:');
        $response->assertDontSee('Hotline:');
    }

    public function test_shows_social_block_when_a_social_url_is_set(): void
    {
        Setting::set('facebook_url', 'https://facebook.com/spaabc');

        $response = $this->get('/');

        $response->assertSee('Kết nối với chúng tôi');
        $response->assertSee('https://facebook.com/spaabc', false);
    }

    public function test_shows_contact_block_when_working_hours_is_set(): void
    {
        Setting::set('working_hours', '8:00 - 20:00');

        $response = $this->get('/');

        $response->assertSee('Giờ làm việc:');
        $response->assertSee('8:00 - 20:00');
    }
}
