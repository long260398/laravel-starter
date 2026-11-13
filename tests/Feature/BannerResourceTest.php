<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Models\Banner;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BannerResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validFormData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Banner khuyến mãi mùa hè',
            'image' => UploadedFile::fake()->image('banner.jpg'),
            'position' => 'home_slider',
            'status' => PublishStatus::Draft,
        ], $overrides);
    }

    public function test_guest_cannot_access_banners(): void
    {
        $response = $this->get('/admin/banners');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_banners(): void
    {
        $this->actingAs(User::factory()->create());

        $banners = Banner::factory()->count(3)->create();

        Livewire::test(ListBanners::class)
            ->assertOk()
            ->assertCanSeeTableRecords($banners);
    }

    public function test_can_create_banner(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData())
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::query()->where('title', 'Banner khuyến mãi mùa hè')->first();

        $this->assertNotNull($banner);
        $this->assertTrue(Storage::disk('public')->exists($banner->image));
    }

    public function test_image_mobile_is_optional(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', [
            'title' => 'Banner khuyến mãi mùa hè',
            'image_mobile' => null,
        ]);
    }

    public static function dangerousLinkUrlProvider(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'javascript scheme mixed case' => ['JaVaScRiPt:alert(1)'],
            'javascript scheme with leading space' => [' javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript scheme' => ['vbscript:msgbox(1)'],
            'protocol relative' => ['//evil.com'],
            'backslash trick' => ['/\\evil.com'],
            'https without host' => ['https://'],
        ];
    }

    #[DataProvider('dangerousLinkUrlProvider')]
    public function test_dangerous_link_urls_are_rejected(string $url): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData(['link_url' => $url]))
            ->call('create')
            ->assertHasFormErrors(['link_url']);

        $this->assertDatabaseMissing('banners', ['link_url' => $url]);
    }

    public static function safeLinkUrlProvider(): array
    {
        return [
            'internal path' => ['/khuyen-mai'],
            'https url' => ['https://example.com'],
        ];
    }

    #[DataProvider('safeLinkUrlProvider')]
    public function test_safe_link_urls_are_accepted(string $url): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData(['link_url' => $url]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', ['link_url' => $url]);
    }

    public function test_cannot_use_a_position_not_declared_in_config(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData(['position' => 'khong_ton_tai']))
            ->call('create')
            ->assertHasFormErrors(['position']);

        $this->assertDatabaseMissing('banners', ['position' => 'khong_ton_tai']);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData([
                'starts_at' => now()->addDays(5),
                'ends_at' => now()->addDay(),
            ]))
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    }

    public function test_can_create_banner_with_valid_schedule(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm($this->validFormData([
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDays(5),
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', ['title' => 'Banner khuyến mãi mùa hè']);
    }

    public function test_new_banner_receives_next_sort_order_automatically(): void
    {
        $first = Banner::factory()->create();
        $second = Banner::factory()->create();

        $this->assertSame($first->sort_order + 1, $second->sort_order);
    }

    public function test_banners_can_be_reordered(): void
    {
        $this->actingAs(User::factory()->create());

        $first = Banner::factory()->create();
        $second = Banner::factory()->create();
        $third = Banner::factory()->create();

        Livewire::test(ListBanners::class)
            ->call('reorderTable', [$third->getKey(), $second->getKey(), $first->getKey()]);

        $this->assertTrue(
            $third->refresh()->sort_order < $second->refresh()->sort_order
            && $second->sort_order < $first->refresh()->sort_order,
        );
    }

    public function test_can_update_banner(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        $imagePath = UploadedFile::fake()->image('cover.jpg')->store('banners', 'public');
        $banner = Banner::factory()->create(['image' => $imagePath]);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['title' => 'Tiêu đề đã sửa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Tiêu đề đã sửa',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_banner(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $banner = Banner::factory()->create();

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('banners', ['id' => $banner->id]);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'deleted_at' => null]);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('banners', ['id' => $banner->id]);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_banner(): void
    {
        $editor = User::factory()->editor()->create();
        $banner = Banner::factory()->create();

        $this->assertTrue($editor->can('viewAny', Banner::class));
        $this->assertTrue($editor->can('view', $banner));
        $this->assertTrue($editor->can('create', Banner::class));
        $this->assertTrue($editor->can('update', $banner));
        $this->assertTrue($editor->can('delete', $banner));
        $this->assertTrue($editor->can('restore', $banner));
        $this->assertFalse($editor->can('forceDelete', $banner));
        $this->assertFalse($editor->can('forceDeleteAny', Banner::class));
    }

    public function test_admin_can_force_delete_banner(): void
    {
        $admin = User::factory()->admin()->create();
        $banner = Banner::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $banner));
        $this->assertTrue($admin->can('forceDeleteAny', Banner::class));
    }
}
