<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_show_filament_version_info(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Filament v');
    }

    public function test_widget_shows_new_contact_message_count(): void
    {
        $this->actingAs(User::factory()->create());

        ContactMessage::factory()->count(2)->create(['status' => ContactStatus::New]);
        ContactMessage::factory()->create(['status' => ContactStatus::Processing]);

        Livewire::test(DashboardStatsWidget::class)
            ->assertSee('Tin liên hệ mới')
            ->assertSee('2');
    }

    public function test_widget_shows_published_post_count(): void
    {
        $this->actingAs(User::factory()->create());

        Post::factory()->published()->count(3)->create();
        Post::factory()->create();

        Livewire::test(DashboardStatsWidget::class)
            ->assertSee('Bài viết đã xuất bản')
            ->assertSee('3');
    }

    public function test_widget_shows_currently_visible_banner_count(): void
    {
        $this->actingAs(User::factory()->create());

        Banner::factory()->published()->forPosition('home_slider')->create();
        Banner::factory()->published()->forPosition('home_middle')->create([
            'starts_at' => now()->addDay(),
        ]);

        Livewire::test(DashboardStatsWidget::class)
            ->assertSee('Banner đang hiển thị')
            ->assertSee('1');
    }
}
