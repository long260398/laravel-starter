<?php

namespace Tests\Unit;

use App\Actions\SaveSettings;
use App\Models\Media;
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

    public function test_handle_keeps_the_old_file_but_moves_its_usage_to_unused(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/old-logo.png', 'old');
        Setting::set('logo', 'settings/old-logo.png');

        app(SaveSettings::class)->handle([
            'logo' => 'settings/new-logo.png',
        ]);

        $this->assertSame('settings/new-logo.png', Setting::get('logo'));
        Storage::disk(config('media.disk'))->assertExists('settings/old-logo.png');
        $this->assertFalse(Media::query()->where('path', 'settings/old-logo.png')->first()->isUsed());
        $this->assertTrue(Media::query()->where('path', 'settings/new-logo.png')->first()->isUsed());
    }

    public function test_handle_rolls_back_usage_changes_on_mid_transaction_failure(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/old-logo.png', 'old');
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
        Storage::disk(config('media.disk'))->assertExists('settings/old-logo.png');
        $this->assertTrue(Media::query()->where('path', 'settings/old-logo.png')->first()->isUsed());
        $this->assertDatabaseMissing('settings', ['key' => 'boom']);
    }

    public function test_handle_does_not_change_usage_when_the_value_is_unchanged(): void
    {
        Storage::fake(config('media.disk'));
        Storage::disk(config('media.disk'))->put('settings/logo.png', 'content');
        Setting::set('logo', 'settings/logo.png');

        app(SaveSettings::class)->handle([
            'logo' => 'settings/logo.png',
        ]);

        Storage::disk(config('media.disk'))->assertExists('settings/logo.png');
        $this->assertTrue(Media::query()->where('path', 'settings/logo.png')->first()->isUsed());
    }
}
