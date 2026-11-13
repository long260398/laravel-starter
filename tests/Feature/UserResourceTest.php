<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_resource(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/admin/login');
    }

    public function test_editor_cannot_access_user_resource(): void
    {
        $editor = User::factory()->editor()->create();

        $response = $this->actingAs($editor)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        $others = User::factory()->count(2)->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($others);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->assertFalse($admin->can('delete', $admin));

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->editor()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $other->getRouteKey()])
            ->assertActionVisible(DeleteAction::class)
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
