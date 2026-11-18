<?php

namespace App\Modules\Faq;

use App\Modules\Faq\Models\Faq;
use App\Modules\Faq\Policies\FaqPolicy;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.faq.enabled') = true — không cần tự kiểm tra lại cờ này ở
 * đây, vì provider này không tồn tại trong request khi module tắt.
 */
class FaqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'faq');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        Gate::policy(Faq::class, FaqPolicy::class);

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerNavItem('faq::nav-item');

        $registry->registerSitemapUrls(function (): array {
            $latestFaq = Faq::query()->published()->latest('updated_at')->first();

            return [
                [
                    'loc' => route('faq.index'),
                    'lastmod' => $latestFaq?->updated_at ?? now(),
                ],
            ];
        });

        $registry->registerThemeViewContract(function (): array {
            $faq = Faq::factory()->published()->create();

            return [
                'faq/index.blade.php' => ['faq::index', [
                    'faqs' => collect([$faq]),
                    'faqSchema' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => [],
                    ],
                ]],
                'faq/nav-item.blade.php' => ['faq::nav-item', []],
            ];
        });
    }
}
