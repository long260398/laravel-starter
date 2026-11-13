<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerVisibilityScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_excludes_draft_banners(): void
    {
        $draft = Banner::factory()->forPosition('home_slider')->create();

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertFalse($visible->contains($draft));
    }

    public function test_excludes_banners_that_have_not_started_yet(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_slider')->create([
            'starts_at' => now()->addDay(),
        ]);

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertFalse($visible->contains($banner));
    }

    public function test_excludes_banners_that_have_already_ended(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_slider')->create([
            'ends_at' => now()->subDay(),
        ]);

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertFalse($visible->contains($banner));
    }

    public function test_excludes_banners_in_a_different_position(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_middle')->create();

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertFalse($visible->contains($banner));
    }

    public function test_includes_published_banner_within_its_time_window(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_slider')->create([
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertTrue($visible->contains($banner));
    }

    public function test_includes_published_banner_with_no_time_restriction(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_slider')->create([
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertTrue($visible->contains($banner));
    }

    public function test_orders_results_by_sort_order(): void
    {
        $first = Banner::factory()->published()->forPosition('home_slider')->create();
        $second = Banner::factory()->published()->forPosition('home_slider')->create();

        $second->sort_order = $first->sort_order - 1;
        $second->save();

        $visible = Banner::query()->visibleAt('home_slider')->get();

        $this->assertSame($second->id, $visible->first()->id);
    }
}
