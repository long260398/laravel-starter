<?php

namespace App\Providers\Filament;

use App\Enums\NavigationGroup;
use App\Http\Middleware\SecurityHeaders;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => setting('site_name', config('app.name')))
            ->brandLogo(fn () => public_file_url(setting('logo')))
            ->brandLogoHeight('2.5rem')
            ->navigationGroups(NavigationGroup::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ]);

        // Resource của mỗi module bật được discover riêng từ thư mục Filament/Resources
        // của module đó — không hardcode tên module nào ở đây. discoverResources() gộp
        // thêm vào danh sách đã có (của core), không thay thế.
        foreach (config('modules', []) as $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $resourcesPath = rtrim($module['path'], '/\\').'/Filament/Resources';

            if (is_dir($resourcesPath)) {
                $panel->discoverResources(in: $resourcesPath, for: $module['namespace'].'\\Filament\\Resources');
            }
        }

        return $panel
            ->middleware([
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
