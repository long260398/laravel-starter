<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Modules\FloatingContact\Filament\Pages\FloatingContactSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FloatingContactSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_access_settings_page(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        $this->assertFalse(FloatingContactSettings::canAccess());

        $response = $this->get('/admin/floating-contact-settings');

        $response->assertForbidden();
    }

    public function test_admin_can_save_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(FloatingContactSettings::class)
            ->assertOk()
            ->fillForm([
                'enable_call' => true,
                'enable_zalo' => true,
                'enable_messenger' => false,
                'position' => 'left',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Setting::get('floating_contact.enable_call'));
        $this->assertTrue(Setting::get('floating_contact.enable_zalo'));
        $this->assertFalse(Setting::get('floating_contact.enable_messenger'));
        $this->assertSame('left', Setting::get('floating_contact.position'));
    }
}
