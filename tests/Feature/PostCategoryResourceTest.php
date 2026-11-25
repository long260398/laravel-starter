<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Filament\Resources\PostCategories\Pages\CreatePostCategory;
use App\Filament\Resources\PostCategories\Pages\EditPostCategory;
use App\Filament\Resources\PostCategories\Pages\ListPostCategories;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PostCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_post_categories(): void
    {
        $response = $this->get('/admin/post-categories');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_post_categories(): void
    {
        $this->actingAs(User::factory()->create());

        $categories = PostCategory::factory()->count(3)->create();

        Livewire::test(ListPostCategories::class)
            ->assertOk()
            ->assertCanSeeTableRecords($categories);
    }

    public function test_can_create_post_category_with_slug_generated_from_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePostCategory::class)
            ->fillForm([
                'name' => 'Mẹo vặt gia đình',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = PostCategory::query()->where('name', 'Mẹo vặt gia đình')->first();

        $this->assertNotNull($category);
        $this->assertSame(Str::slug('Mẹo vặt gia đình'), $category->slug);
    }

    public function test_new_category_gets_next_sort_order(): void
    {
        PostCategory::factory()->create(['sort_order' => 5]);

        $category = PostCategory::factory()->create();

        $this->assertSame(6, $category->sort_order);
    }

    public function test_can_update_post_category(): void
    {
        $this->actingAs(User::factory()->create());

        $category = PostCategory::factory()->create();

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => 'Tên đã sửa',
                'slug' => 'ten-da-sua',
                'description' => 'Mô tả mới',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('post_categories', [
            'id' => $category->id,
            'name' => 'Tên đã sửa',
            'slug' => 'ten-da-sua',
            'description' => 'Mô tả mới',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_post_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $category = PostCategory::factory()->create();

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('post_categories', ['id' => $category->id]);

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('post_categories', ['id' => $category->id, 'deleted_at' => null]);

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('post_categories', ['id' => $category->id]);
    }

    public function test_deleting_category_keeps_its_posts_with_null_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $category = PostCategory::factory()->create();
        $post = Post::factory()->for($category, 'category')->create();

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class);

        Livewire::test(EditPostCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'category_id' => null]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_post_category(): void
    {
        $editor = User::factory()->editor()->create();
        $category = PostCategory::factory()->create();

        $this->assertTrue($editor->can('viewAny', PostCategory::class));
        $this->assertTrue($editor->can('update', $category));
        $this->assertTrue($editor->can('delete', $category));
        $this->assertFalse($editor->can('forceDelete', $category));
    }

    public function test_admin_can_force_delete_post_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = PostCategory::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $category));
    }
}
