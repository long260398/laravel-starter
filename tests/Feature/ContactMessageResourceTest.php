<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessages\Pages\EditContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_contact_messages(): void
    {
        $response = $this->get('/admin/contact-messages');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_contact_messages(): void
    {
        $this->actingAs(User::factory()->create());

        $contactMessages = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)
            ->assertOk()
            ->assertCanSeeTableRecords($contactMessages);
    }

    public function test_create_route_does_not_exist(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get('/admin/contact-messages/create');

        $response->assertNotFound();
    }

    public function test_editor_can_update_status_and_note_but_cannot_delete(): void
    {
        $editor = User::factory()->editor()->create();
        $contactMessage = ContactMessage::factory()->create();

        $this->assertTrue($editor->can('viewAny', ContactMessage::class));
        $this->assertTrue($editor->can('view', $contactMessage));
        $this->assertFalse($editor->can('create', ContactMessage::class));
        $this->assertTrue($editor->can('update', $contactMessage));
        $this->assertFalse($editor->can('delete', $contactMessage));
        $this->assertFalse($editor->can('deleteAny', ContactMessage::class));

        $this->actingAs($editor);

        Livewire::test(EditContactMessage::class, ['record' => $contactMessage->getRouteKey()])
            ->fillForm([
                'status' => ContactStatus::Processing,
                'admin_note' => 'Đang gọi lại cho khách.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $contactMessage->id,
            'status' => ContactStatus::Processing->value,
            'admin_note' => 'Đang gọi lại cho khách.',
        ]);
    }

    public function test_admin_can_delete_contact_message(): void
    {
        $admin = User::factory()->admin()->create();
        $contactMessage = ContactMessage::factory()->create();

        $this->assertTrue($admin->can('delete', $contactMessage));
        $this->assertTrue($admin->can('deleteAny', ContactMessage::class));

        $this->actingAs($admin);

        Livewire::test(EditContactMessage::class, ['record' => $contactMessage->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('contact_messages', ['id' => $contactMessage->id]);
    }
}
