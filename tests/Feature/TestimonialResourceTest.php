<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Modules\Testimonials\Models\Testimonial;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TestimonialResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_testimonials(): void
    {
        $response = $this->get('/admin/testimonials');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_testimonials(): void
    {
        $this->actingAs(User::factory()->create());

        $testimonials = Testimonial::factory()->count(3)->create();

        Livewire::test(ListTestimonials::class)
            ->assertOk()
            ->assertCanSeeTableRecords($testimonials);
    }

    public function test_can_create_testimonial(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateTestimonial::class)
            ->fillForm([
                'customer_name' => 'Nguyễn Văn A',
                'customer_title' => 'Khách hàng thân thiết',
                'content' => 'Dịch vụ rất tốt.',
                'rating' => 5,
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('testimonials', [
            'customer_name' => 'Nguyễn Văn A',
            'rating' => 5,
        ]);
    }

    public function test_can_update_testimonial(): void
    {
        $this->actingAs(User::factory()->create());

        $testimonial = Testimonial::factory()->create();

        Livewire::test(EditTestimonial::class, ['record' => $testimonial->getRouteKey()])
            ->fillForm([
                'customer_name' => 'Tên đã sửa',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'customer_name' => 'Tên đã sửa',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_testimonial(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $testimonial = Testimonial::factory()->create();

        Livewire::test(EditTestimonial::class, ['record' => $testimonial->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('testimonials', ['id' => $testimonial->id]);

        Livewire::test(EditTestimonial::class, ['record' => $testimonial->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id, 'deleted_at' => null]);

        Livewire::test(EditTestimonial::class, ['record' => $testimonial->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditTestimonial::class, ['record' => $testimonial->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_testimonial(): void
    {
        $editor = User::factory()->editor()->create();
        $testimonial = Testimonial::factory()->create();

        $this->assertTrue($editor->can('viewAny', Testimonial::class));
        $this->assertTrue($editor->can('update', $testimonial));
        $this->assertTrue($editor->can('delete', $testimonial));
        $this->assertFalse($editor->can('forceDelete', $testimonial));
    }

    public function test_admin_can_force_delete_testimonial(): void
    {
        $admin = User::factory()->admin()->create();
        $testimonial = Testimonial::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $testimonial));
    }
}
