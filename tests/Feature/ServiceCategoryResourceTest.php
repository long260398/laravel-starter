<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\CreateServiceCategory;
use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\EditServiceCategory;
use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\ListServiceCategories;
use App\Modules\Services\Models\ServiceCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_service_categories(): void
    {
        $response = $this->get('/admin/service-categories');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_service_categories(): void
    {
        $this->actingAs(User::factory()->create());

        $categories = ServiceCategory::factory()->count(3)->create();

        Livewire::test(ListServiceCategories::class)
            ->assertOk()
            ->assertCanSeeTableRecords($categories);
    }

    public function test_can_create_service_category_with_slug_generated_from_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateServiceCategory::class)
            ->fillForm([
                'name' => 'Chăm sóc da',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = ServiceCategory::query()->where('name', 'Chăm sóc da')->first();

        $this->assertNotNull($category);
        $this->assertSame(Str::slug('Chăm sóc da'), $category->slug);
    }

    public function test_can_update_service_category(): void
    {
        $this->actingAs(User::factory()->create());

        $category = ServiceCategory::factory()->create();

        Livewire::test(EditServiceCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => 'Tên đã sửa',
                'slug' => 'ten-da-sua',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Tên đã sửa',
            'slug' => 'ten-da-sua',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_service_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $category = ServiceCategory::factory()->create();

        Livewire::test(EditServiceCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('service_categories', ['id' => $category->id]);

        Livewire::test(EditServiceCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('service_categories', ['id' => $category->id, 'deleted_at' => null]);

        Livewire::test(EditServiceCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditServiceCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('service_categories', ['id' => $category->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_service_category(): void
    {
        $editor = User::factory()->editor()->create();
        $category = ServiceCategory::factory()->create();

        $this->assertTrue($editor->can('viewAny', ServiceCategory::class));
        $this->assertTrue($editor->can('update', $category));
        $this->assertTrue($editor->can('delete', $category));
        $this->assertFalse($editor->can('forceDelete', $category));
    }

    public function test_admin_can_force_delete_service_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $category));
    }
}
