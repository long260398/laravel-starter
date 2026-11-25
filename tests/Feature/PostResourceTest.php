<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_posts(): void
    {
        $response = $this->get('/admin/posts');

        $response->assertRedirect('/admin/login');
    }

    public function test_can_list_posts(): void
    {
        $this->actingAs(User::factory()->create());

        $posts = Post::factory()->count(3)->create();

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertCanSeeTableRecords($posts);
    }

    public function test_can_create_post_with_slug_generated_from_title(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Tiêu đề bài viết đầu tiên',
                'content' => 'Nội dung bài viết.',
                'status' => PublishStatus::Draft,
                'featured_image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('title', 'Tiêu đề bài viết đầu tiên')->first();

        $this->assertNotNull($post);
        $this->assertSame(Str::slug('Tiêu đề bài viết đầu tiên'), $post->slug);
        $this->assertTrue(Storage::disk('public')->exists($post->featured_image));
    }

    public function test_publishing_a_post_fills_published_at_automatically(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Bài viết xuất bản ngay',
                'slug' => 'bai-viet-xuat-ban-ngay',
                'content' => 'Nội dung.',
                'status' => PublishStatus::Published,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'bai-viet-xuat-ban-ngay')->first();

        $this->assertNotNull($post);
        $this->assertSame(PublishStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_can_update_post(): void
    {
        $this->actingAs(User::factory()->create());

        $post = Post::factory()->create();

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => 'Tiêu đề đã sửa',
                'slug' => 'tieu-de-da-sua',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Tiêu đề đã sửa',
            'slug' => 'tieu-de-da-sua',
        ]);
    }

    public function test_can_soft_delete_restore_and_force_delete_post(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $post = Post::factory()->create();

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(ForceDeleteAction::class);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_editor_has_full_crud_but_cannot_force_delete_post(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create();

        $this->assertTrue($editor->can('viewAny', Post::class));
        $this->assertTrue($editor->can('view', $post));
        $this->assertTrue($editor->can('create', Post::class));
        $this->assertTrue($editor->can('update', $post));
        $this->assertTrue($editor->can('delete', $post));
        $this->assertTrue($editor->can('restore', $post));
        $this->assertFalse($editor->can('forceDelete', $post));
        $this->assertFalse($editor->can('forceDeleteAny', Post::class));
    }

    public function test_admin_can_force_delete_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $post));
        $this->assertTrue($admin->can('forceDeleteAny', Post::class));
    }

    public function test_creating_post_with_new_tag_names_creates_and_attaches_tags(): void
    {
        $this->actingAs(User::factory()->create());
        $category = PostCategory::factory()->create();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Bài viết có thẻ mới',
                'slug' => 'bai-viet-co-the-moi',
                'category_id' => $category->id,
                'tag_names' => ['Mẹo vặt', 'Gia đình'],
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'bai-viet-co-the-moi')->first();

        $this->assertNotNull($post);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame(['Mẹo vặt', 'Gia đình'], $post->tags->pluck('name')->all());
    }

    public function test_editing_post_tags_replaces_previous_tags(): void
    {
        $this->actingAs(User::factory()->create());

        $post = Post::factory()->create();
        $existingTag = Tag::factory()->create(['name' => 'Cũ']);
        $post->tags()->attach($existingTag);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['tag_names' => ['Mới']])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame(['Mới'], $post->tags->pluck('name')->all());
    }

    public function test_reusing_an_existing_tag_name_does_not_create_a_duplicate(): void
    {
        $this->actingAs(User::factory()->create());
        Tag::factory()->create(['name' => 'Mẹo vặt', 'slug' => 'meo-vat']);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Bài viết dùng thẻ có sẵn',
                'slug' => 'bai-viet-dung-the-co-san',
                'tag_names' => ['Mẹo vặt'],
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Tag::query()->where('slug', 'meo-vat')->count());
    }

    public function test_cannot_create_post_with_reserved_slug(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Bài viết trùng từ khoá hệ thống',
                'slug' => 'search',
                'content' => 'Nội dung.',
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_safe_content_strips_unsafe_html(): void
    {
        $post = Post::factory()->make([
            'content' => '<p>Nội dung hợp lệ</p><script>alert("xss")</script>',
        ]);

        $this->assertStringContainsString('<p>Nội dung hợp lệ</p>', $post->safe_content);
        $this->assertStringNotContainsString('<script>', $post->safe_content);
    }
}
