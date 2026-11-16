<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Modules\Services\Filament\Resources\Services\Pages\CreateService;
use App\Modules\Services\Filament\Resources\Services\Pages\EditService;
use App\Modules\Services\Filament\Resources\Services\Pages\ListServices;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_services(): void
    {
        $response = $this->get('/admin/services');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_services(): void
    {
        $this->actingAs(User::factory()->create());

        $services = Service::factory()->count(3)->create();

        Livewire::test(ListServices::class)
            ->assertOk()
            ->assertCanSeeTableRecords($services);
    }

    public function test_can_create_service_with_slug_generated_from_name(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $category = ServiceCategory::factory()->create();

        Livewire::test(CreateService::class)
            ->fillForm([
                'service_category_id' => $category->id,
                'name' => 'Massage thư giãn',
                'content' => 'Nội dung dịch vụ.',
                'status' => PublishStatus::Draft,
                'image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::query()->where('name', 'Massage thư giãn')->first();

        $this->assertNotNull($service);
        $this->assertSame(Str::slug('Massage thư giãn'), $service->slug);
        $this->assertTrue(Storage::disk('public')->exists($service->image));
    }

    public function test_can_update_service(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->fillForm([
                'name' => 'Tên đã sửa',
                'slug' => 'ten-da-sua',
                'price' => 500000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Tên đã sửa',
            'slug' => 'ten-da-sua',
            'price' => 500000,
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_service(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $service = Service::factory()->create();

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('services', ['id' => $service->id]);

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_service(): void
    {
        $editor = User::factory()->editor()->create();
        $service = Service::factory()->create();

        $this->assertTrue($editor->can('viewAny', Service::class));
        $this->assertTrue($editor->can('update', $service));
        $this->assertTrue($editor->can('delete', $service));
        $this->assertFalse($editor->can('forceDelete', $service));
    }

    public function test_admin_can_force_delete_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $service));
    }

    public function test_safe_content_strips_unsafe_html(): void
    {
        $service = Service::factory()->make([
            'content' => '<p>Nội dung hợp lệ</p><script>alert("xss")</script>',
        ]);

        $this->assertStringContainsString('<p>Nội dung hợp lệ</p>', $service->safe_content);
        $this->assertStringNotContainsString('<script>', $service->safe_content);
    }

    public function test_price_label_shows_lien_he_when_price_is_null(): void
    {
        $service = Service::factory()->make(['price' => null]);

        $this->assertSame('Liên hệ', $service->price_label);
    }

    public function test_price_label_formats_price_with_vnd_suffix(): void
    {
        $service = Service::factory()->make(['price' => 1200000, 'price_note' => null]);

        $this->assertSame('1.200.000đ', $service->price_label);
    }

    public function test_price_label_appends_note_starting_with_slash_after_price(): void
    {
        $service = Service::factory()->make(['price' => 1200000, 'price_note' => '/buổi']);

        $this->assertSame('1.200.000đ/buổi', $service->price_label);
    }

    public function test_price_label_prefixes_note_not_starting_with_slash_before_price(): void
    {
        $service = Service::factory()->make(['price' => 500000, 'price_note' => 'Từ']);

        $this->assertSame('Từ 500.000đ', $service->price_label);
    }
}
