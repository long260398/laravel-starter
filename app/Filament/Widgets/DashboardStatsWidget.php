<?php

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Enums\PublishStatus;
use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Tin liên hệ mới',
                (string) ContactMessage::query()->where('status', ContactStatus::New)->count(),
            ),
            Stat::make(
                'Bài viết đã xuất bản',
                (string) Post::query()->where('status', PublishStatus::Published)->count(),
            ),
            Stat::make(
                'Banner đang hiển thị',
                (string) Banner::query()->visible()->count(),
            ),
        ];
    }
}
