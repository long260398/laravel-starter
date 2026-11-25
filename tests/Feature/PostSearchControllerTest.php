<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_with_short_query_does_not_query_posts(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Bài viết mẫu']);

        $response = $this->get('/blog/search?q=a');

        $response->assertStatus(200)
            ->assertDontSee($post->title)
            ->assertSee('ít nhất 2 ký tự');
    }

    public function test_search_finds_published_post_by_title(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Cách chọn tủ lạnh tiết kiệm điện']);

        $this->get('/blog/search?q=tủ lạnh')->assertSee($post->title);
    }

    public function test_search_does_not_return_draft_posts(): void
    {
        $draft = Post::factory()->create(['title' => 'Bài viết nháp đặc biệt']);

        $this->get('/blog/search?q=đặc biệt')->assertDontSee($draft->title);
    }

    public function test_search_escapes_percent_wildcard_in_keyword(): void
    {
        // Không escape thì "0%" sẽ thành "chứa 0, theo sau là bất kỳ gì" (vì %
        // thường tự là wildcard), nên sẽ khớp nhầm cả tiêu đề chỉ chứa "0".
        $withLiteralPercent = Post::factory()->published()->create(['title' => 'Giảm giá 50% hôm nay']);
        $withDigitZeroOnly = Post::factory()->published()->create(['title' => 'Giá 509 tốt']);

        $this->get('/blog/search?q=0%')
            ->assertSee($withLiteralPercent->title)
            ->assertDontSee($withDigitZeroOnly->title);
    }

    public function test_search_escapes_underscore_wildcard_in_keyword(): void
    {
        // Không escape thì "_" khớp với bất kỳ 1 ký tự nào, nên "a_" sẽ khớp
        // nhầm cả tiêu đề có "ab" (không chứa gạch dưới thật).
        $withLiteralUnderscore = Post::factory()->published()->create(['title' => 'Mau a_test dac biet']);
        $withAnyCharInstead = Post::factory()->published()->create(['title' => 'Mau abtest dac biet']);

        $this->get('/blog/search?q=a_')
            ->assertSee($withLiteralUnderscore->title)
            ->assertDontSee($withAnyCharInstead->title);
    }

    public function test_search_escapes_xss_in_query_output(): void
    {
        $response = $this->get('/blog/search?'.http_build_query(['q' => '<script>alert(1)</script>']));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_search_is_noindex(): void
    {
        $this->get('/blog/search?q=abc')->assertSee('name="robots" content="noindex,follow"', false);
    }

    public function test_search_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get('/blog/search?q=abc')->assertStatus(200);
        }

        $this->get('/blog/search?q=abc')->assertStatus(429);
    }
}
