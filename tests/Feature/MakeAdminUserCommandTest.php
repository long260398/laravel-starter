<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_admin_when_email_does_not_exist(): void
    {
        $this->artisan('app:make-admin', [
            '--name' => 'Chủ sở hữu',
            '--email' => 'owner@example.com',
            '--password' => 'password123',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_it_promotes_an_existing_user_to_admin(): void
    {
        $editor = User::factory()->editor()->inactive()->create(['email' => 'editor@example.com']);

        $this->artisan('app:make-admin', [
            '--email' => 'editor@example.com',
        ])->assertSuccessful();

        $editor->refresh();

        $this->assertSame(UserRole::Admin, $editor->role);
        $this->assertTrue($editor->is_active);
    }
}
