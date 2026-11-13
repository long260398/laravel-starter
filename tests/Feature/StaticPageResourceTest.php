<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Filament\Resources\StaticPages\Pages\CreateStaticPage;
use App\Filament\Resources\StaticPages\Pages\EditStaticPage;
use App\Filament\Resources\StaticPages\Pages\ListStaticPages;
use App\Models\StaticPage;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StaticPageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_static_pages(): void
    {
        $response = $this->get('/admin/static-pages');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_static_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $pages = StaticPage::factory()->count(3)->create();

        Livewire::test(ListStaticPages::class)
            ->assertOk()
            ->assertCanSeeTableRecords($pages);
    }

    public function test_can_create_static_page_with_slug_generated_from_title(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateStaticPage::class)
            ->fillForm([
                'title' => 'Giới thiệu công ty',
                'content' => 'Nội dung giới thiệu.',
                'status' => PublishStatus::Draft,
                'featured_image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = StaticPage::query()->where('title', 'Giới thiệu công ty')->first();

        $this->assertNotNull($page);
        $this->assertSame(Str::slug('Giới thiệu công ty'), $page->slug);
        $this->assertTrue(Storage::disk('public')->exists($page->featured_image));
    }

    public function test_cannot_use_a_statically_reserved_slug(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateStaticPage::class)
            ->fillForm([
                'title' => 'Sitemap',
                'slug' => 'sitemap.xml',
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseMissing('static_pages', ['slug' => 'sitemap.xml']);
    }

    public function test_cannot_use_a_slug_matching_a_registered_route(): void
    {
        $this->actingAs(User::factory()->create());

        // Đăng ký một route bất kỳ không nằm trong danh sách cấm tĩnh, để xác nhận
        // việc kiểm tra động theo Route::getRoutes() hoạt động độc lập với danh sách tĩnh.
        $slug = 'khu-vuc-dac-biet';
        $this->assertFalse(in_array($slug, StaticPage::RESERVED_SLUGS, true));

        Route::get("{$slug}/thong-tin", fn () => null);

        Livewire::test(CreateStaticPage::class)
            ->fillForm([
                'title' => 'Khu vực đặc biệt',
                'slug' => $slug,
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseMissing('static_pages', ['slug' => $slug]);
    }

    public function test_can_use_a_normal_slug_not_matching_any_route(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateStaticPage::class)
            ->fillForm([
                'title' => 'Chính sách bảo mật',
                'slug' => 'chinh-sach-bao-mat',
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('static_pages', ['slug' => 'chinh-sach-bao-mat']);
    }

    public function test_new_static_page_receives_next_sort_order_automatically(): void
    {
        $first = StaticPage::factory()->create();
        $second = StaticPage::factory()->create();

        $this->assertSame($first->sort_order + 1, $second->sort_order);
    }

    public function test_static_pages_can_be_reordered(): void
    {
        $this->actingAs(User::factory()->create());

        $first = StaticPage::factory()->create();
        $second = StaticPage::factory()->create();
        $third = StaticPage::factory()->create();

        Livewire::test(ListStaticPages::class)
            ->call('reorderTable', [$third->getKey(), $second->getKey(), $first->getKey()]);

        $this->assertTrue(
            $third->refresh()->sort_order < $second->refresh()->sort_order
            && $second->sort_order < $first->refresh()->sort_order,
        );
    }

    public function test_can_update_static_page(): void
    {
        $this->actingAs(User::factory()->create());

        $page = StaticPage::factory()->create();

        Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'title' => 'Tiêu đề đã sửa',
                'slug' => 'tieu-de-da-sua',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('static_pages', [
            'id' => $page->id,
            'title' => 'Tiêu đề đã sửa',
            'slug' => 'tieu-de-da-sua',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_static_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $page = StaticPage::factory()->create();

        Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('static_pages', ['id' => $page->id]);

        Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('static_pages', ['id' => $page->id, 'deleted_at' => null]);

        Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('static_pages', ['id' => $page->id]);

        Livewire::test(EditStaticPage::class, ['record' => $page->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('static_pages', ['id' => $page->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_static_page(): void
    {
        $editor = User::factory()->editor()->create();
        $page = StaticPage::factory()->create();

        $this->assertTrue($editor->can('viewAny', StaticPage::class));
        $this->assertTrue($editor->can('view', $page));
        $this->assertTrue($editor->can('create', StaticPage::class));
        $this->assertTrue($editor->can('update', $page));
        $this->assertTrue($editor->can('delete', $page));
        $this->assertTrue($editor->can('restore', $page));
        $this->assertFalse($editor->can('forceDelete', $page));
        $this->assertFalse($editor->can('forceDeleteAny', StaticPage::class));
    }

    public function test_admin_can_force_delete_static_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = StaticPage::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $page));
        $this->assertTrue($admin->can('forceDeleteAny', StaticPage::class));
    }

    public function test_safe_content_strips_unsafe_html(): void
    {
        $page = StaticPage::factory()->make([
            'content' => '<p>Nội dung hợp lệ</p><script>alert("xss")</script>',
        ]);

        $this->assertStringContainsString('<p>Nội dung hợp lệ</p>', $page->safe_content);
        $this->assertStringNotContainsString('<script>', $page->safe_content);
    }
}
