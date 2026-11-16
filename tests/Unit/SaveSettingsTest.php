<?php

namespace Tests\Unit;

use App\Actions\SaveSettings;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SaveSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_saves_multiple_keys(): void
    {
        app(SaveSettings::class)->handle([
            'site_name' => 'Spa ABC',
            'hotline' => '0909123456',
        ]);

        $this->assertSame('Spa ABC', Setting::get('site_name'));
        $this->assertSame('0909123456', Setting::get('hotline'));
    }

    public function test_handle_deletes_the_old_file_only_after_a_successful_save(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/old-logo.png', 'old');
        Setting::set('logo', 'settings/old-logo.png');

        app(SaveSettings::class)->handle([
            'logo' => 'settings/new-logo.png',
        ]);

        $this->assertSame('settings/new-logo.png', Setting::get('logo'));
        Storage::disk('public')->assertMissing('settings/old-logo.png');
    }

    public function test_handle_keeps_the_old_file_and_rolls_back_the_database_on_mid_transaction_failure(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/old-logo.png', 'old');
        Setting::set('logo', 'settings/old-logo.png');

        $hasThrown = false;

        DB::listen(function ($query) use (&$hasThrown): void {
            if (! $hasThrown && in_array('boom', $query->bindings, true)) {
                $hasThrown = true;

                throw new RuntimeException('Simulated mid-transaction failure.');
            }
        });

        try {
            app(SaveSettings::class)->handle([
                'logo' => 'settings/new-logo.png',
                'boom' => 'trigger-failure',
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated mid-transaction failure.', $e->getMessage());
        }

        $this->assertSame('settings/old-logo.png', Setting::get('logo'));
        Storage::disk('public')->assertExists('settings/old-logo.png');
        $this->assertDatabaseMissing('settings', ['key' => 'boom']);
    }

    public function test_handle_does_not_queue_a_file_for_deletion_when_the_value_is_unchanged(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/logo.png', 'content');
        Setting::set('logo', 'settings/logo.png');

        app(SaveSettings::class)->handle([
            'logo' => 'settings/logo.png',
        ]);

        Storage::disk('public')->assertExists('settings/logo.png');
    }
}
