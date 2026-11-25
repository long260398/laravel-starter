<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Booking\Filament\Resources\Bookings\Pages\EditBooking;
use App\Modules\Booking\Filament\Resources\Bookings\Pages\ListBookings;
use App\Modules\Booking\Models\Booking;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_update_status_and_note_but_cannot_delete(): void
    {
        $editor = User::factory()->editor()->create();
        $booking = Booking::factory()->create();

        $this->assertTrue($editor->can('viewAny', Booking::class));
        $this->assertTrue($editor->can('view', $booking));
        $this->assertFalse($editor->can('create', Booking::class));
        $this->assertTrue($editor->can('update', $booking));
        $this->assertFalse($editor->can('delete', $booking));
        $this->assertFalse($editor->can('deleteAny', Booking::class));

        $this->actingAs($editor);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm([
                'status' => 'confirmed',
                'admin_note' => 'Đã gọi xác nhận.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'admin_note' => 'Đã gọi xác nhận.',
        ]);
    }

    public function test_editor_cannot_delete_booking_via_record_action(): void
    {
        $editor = User::factory()->editor()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($editor);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_editor_cannot_delete_booking_via_bulk_action(): void
    {
        $editor = User::factory()->editor()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($editor);

        Livewire::test(ListBookings::class)
            ->assertTableBulkActionHidden('delete');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_admin_can_delete_booking_via_record_action(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        $this->assertTrue($admin->can('delete', $booking));
        $this->assertTrue($admin->can('deleteAny', Booking::class));

        $this->actingAs($admin);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_admin_can_delete_booking_via_bulk_action(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListBookings::class)
            ->callTableBulkAction('delete', [$booking]);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}
