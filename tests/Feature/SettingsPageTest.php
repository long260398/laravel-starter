<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_access_settings_page(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        $this->assertFalse(Settings::canAccess());

        $response = $this->get('/admin/settings');

        $response->assertForbidden();
    }

    public function test_admin_can_save_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->assertOk()
            ->fillForm([
                'site_name' => 'Spa ABC',
                'hotline' => '0909123456',
                'contact_email' => 'lienhe@spaabc.vn',
                'google_maps_url' => 'https://maps.app.goo.gl/abc123',
                'facebook_url' => 'https://facebook.com/spaabc',
                'ga_measurement_id' => 'G-ABCD1234',
                'fb_pixel_id' => '1234567890',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Spa ABC', Setting::get('site_name'));
        $this->assertSame('0909123456', Setting::get('hotline'));
        $this->assertSame('G-ABCD1234', Setting::get('ga_measurement_id'));
    }

    public function test_invalid_tracking_ids_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => 'Spa ABC',
                'ga_measurement_id' => 'UA-12345',
                'fb_pixel_id' => 'not-a-number',
            ])
            ->call('save')
            ->assertHasFormErrors(['ga_measurement_id', 'fb_pixel_id']);
    }

    public function test_spoofed_google_maps_host_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => 'Spa ABC',
                'google_maps_url' => 'https://google.com.evil.com/maps',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_maps_url']);
    }

    public function test_valid_google_maps_url_is_accepted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => 'Spa ABC',
                'google_maps_url' => 'https://www.google.com/maps/place/Spa+ABC',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://www.google.com/maps/place/Spa+ABC', Setting::get('google_maps_url'));
    }

    public function test_social_url_must_be_https(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->fillForm([
                'site_name' => 'Spa ABC',
                'facebook_url' => 'http://facebook.com/spaabc',
            ])
            ->call('save')
            ->assertHasFormErrors(['facebook_url']);
    }

    public function test_setting_helper_returns_value_and_default(): void
    {
        Setting::set('site_name', 'Spa ABC');

        $this->assertSame('Spa ABC', setting('site_name'));
        $this->assertSame('mặc định', setting('khong_ton_tai', 'mặc định'));
    }

    public function test_cache_is_cleared_after_saving_a_setting(): void
    {
        Setting::set('site_name', 'Spa ABC');
        $this->assertSame('Spa ABC', setting('site_name'));

        Setting::set('site_name', 'Spa XYZ');

        $this->assertSame('Spa XYZ', setting('site_name'));
    }

    public function test_replacing_the_logo_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->image('old.png')->store('settings', 'public');
        Setting::set('logo', $oldPath);

        $newPath = UploadedFile::fake()->image('new.png')->store('settings', 'public');
        Setting::set('logo', $newPath);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }
}
