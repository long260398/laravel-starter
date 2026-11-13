<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_active_user_can_access_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_active_user_can_access_panel_in_production(): void
    {
        $this->app['env'] = 'production';

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }
}
