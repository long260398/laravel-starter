<?php

namespace Tests\Feature;

use App\Actions\BuildPostJsonLd;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildPostJsonLdTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_schema_has_required_fields(): void
    {
        $post = Post::factory()->published()->create();

        $schema = (new BuildPostJsonLd)->handle($post);

        $this->assertSame('https://schema.org', $schema['article']['@context']);
        $this->assertSame('Article', $schema['article']['@type']);
        $this->assertSame($post->title, $schema['article']['headline']);
        $this->assertNotNull($schema['article']['datePublished']);
    }

    public function test_breadcrumb_includes_category_when_present(): void
    {
        $category = PostCategory::factory()->published()->create();
        $post = Post::factory()->published()->for($category, 'category')->create();

        $schema = (new BuildPostJsonLd)->handle($post);
        $names = array_column($schema['breadcrumb']['itemListElement'], 'name');

        $this->assertSame(['Trang chủ', 'Blog', $category->name, $post->title], $names);
    }

    public function test_breadcrumb_excludes_category_when_absent(): void
    {
        $post = Post::factory()->published()->create();

        $schema = (new BuildPostJsonLd)->handle($post);
        $names = array_column($schema['breadcrumb']['itemListElement'], 'name');

        $this->assertSame(['Trang chủ', 'Blog', $post->title], $names);
    }
}
