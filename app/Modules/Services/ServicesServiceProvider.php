<?php

namespace App\Modules\Services;

use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use App\Modules\Services\Policies\ServiceCategoryPolicy;
use App\Modules\Services\Policies\ServicePolicy;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.services.enabled') = true — không cần tự kiểm tra lại cờ
 * này ở đây, vì provider này không tồn tại trong request khi module tắt.
 */
class ServicesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'services');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerNavItem('services::nav-item');

        $registry->registerHomeSection('services::home-section', fn (): array => [
            'services' => Service::query()->published()->featured()->orderBy('sort_order')->take(6)->get(),
        ]);

        $registry->registerBookableSource('services', 'Dịch vụ', fn () => Service::query()->published()
            ->orderBy('sort_order')
            ->get(['slug', 'name'])
            ->map(fn (Service $service): array => ['slug' => $service->slug, 'label' => $service->name])
            ->all());

        $registry->registerSitemapUrls(fn () => Service::query()->published()->get()->map(
            fn (Service $service): array => [
                'loc' => route('services.show', $service->slug),
                'lastmod' => $service->updated_at,
            ],
        ));

        $registry->registerThemeViewContract(function (): array {
            $category = ServiceCategory::factory()->published()->create();
            $service = Service::factory()->published()->for($category, 'category')->create();

            return [
                'services/index.blade.php' => ['services::index', [
                    'categories' => collect([$category]),
                    'services' => collect([$service]),
                    'activeCategory' => '',
                ]],
                'services/show.blade.php' => ['services::show', ['service' => $service]],
                'services/nav-item.blade.php' => ['services::nav-item', []],
                'services/home-section.blade.php' => ['services::home-section', ['services' => collect([$service])]],
                'services/components/service-card.blade.php' => ['services::components.service-card', ['service' => $service]],
            ];
        });
    }
}
