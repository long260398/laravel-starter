<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloatingContactWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_buttons_are_shown_when_nothing_is_enabled(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('aria-label="Gọi điện"', false)
            ->assertDontSee('aria-label="Chat Zalo"', false)
            ->assertDontSee('aria-label="Chat Messenger"', false);
    }

    public function test_call_button_is_hidden_when_hotline_is_missing_even_if_enabled(): void
    {
        Setting::set('floating_contact.enable_call', true);

        $this->get('/')->assertDontSee('aria-label="Gọi điện"', false);
    }

    public function test_call_button_shows_sanitized_tel_link_when_enabled_with_hotline(): void
    {
        Setting::set('floating_contact.enable_call', true);
        Setting::set('hotline', '(028) 1234-5678');

        $this->get('/')
            ->assertSee('aria-label="Gọi điện"', false)
            ->assertSee('tel:02812345678', false);
    }

    public function test_zalo_button_shows_link_built_from_raw_phone_number(): void
    {
        Setting::set('floating_contact.enable_zalo', true);
        Setting::set('zalo_url', '0909123456');

        $this->get('/')
            ->assertSee('aria-label="Chat Zalo"', false)
            ->assertSee('https://zalo.me/0909123456', false);
    }

    public function test_zalo_button_is_hidden_when_url_points_to_another_domain(): void
    {
        Setting::set('floating_contact.enable_zalo', true);
        Setting::set('zalo_url', 'https://evil.com/0909123456');

        $this->get('/')->assertDontSee('aria-label="Chat Zalo"', false);
    }

    public function test_messenger_button_shows_link_when_enabled_with_valid_url(): void
    {
        Setting::set('floating_contact.enable_messenger', true);
        Setting::set('messenger_url', 'https://m.me/spa.abc');

        $this->get('/')
            ->assertSee('aria-label="Chat Messenger"', false)
            ->assertSee('https://m.me/spa.abc', false);
    }

    public function test_messenger_button_is_hidden_when_url_is_invalid(): void
    {
        Setting::set('floating_contact.enable_messenger', true);
        Setting::set('messenger_url', 'https://facebook.com/spa.abc');

        $this->get('/')->assertDontSee('aria-label="Chat Messenger"', false);
    }

    public function test_position_setting_controls_which_side_the_widget_is_pinned_to(): void
    {
        Setting::set('floating_contact.enable_call', true);
        Setting::set('hotline', '0909123456');
        Setting::set('floating_contact.position', 'left');

        $this->get('/')->assertSee('left-4', false);
    }
}
