<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UserAdminGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_demote_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(RuntimeException::class);

        $admin->update(['role' => UserRole::Editor]);
    }

    public function test_cannot_deactivate_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(RuntimeException::class);

        $admin->update(['is_active' => false]);
    }

    public function test_cannot_delete_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(RuntimeException::class);

        $admin->delete();
    }

    public function test_can_demote_an_admin_when_another_active_admin_remains(): void
    {
        User::factory()->admin()->create();
        $second = User::factory()->admin()->create();

        $second->update(['role' => UserRole::Editor]);

        $this->assertSame(UserRole::Editor, $second->refresh()->role);
    }

    public function test_can_deactivate_an_admin_when_another_active_admin_remains(): void
    {
        User::factory()->admin()->create();
        $second = User::factory()->admin()->create();

        $second->update(['is_active' => false]);

        $this->assertFalse($second->refresh()->is_active);
    }

    public function test_can_delete_an_admin_when_another_active_admin_remains(): void
    {
        User::factory()->admin()->create();
        $second = User::factory()->admin()->create();

        $second->delete();

        $this->assertDatabaseMissing('users', ['id' => $second->id]);
    }

    public function test_updating_an_editor_is_unaffected_by_the_admin_guard(): void
    {
        User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $editor->update(['name' => 'Tên mới']);

        $this->assertSame('Tên mới', $editor->refresh()->name);
    }
}
