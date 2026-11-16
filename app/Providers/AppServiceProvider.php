<?php

namespace App\Providers;

use App\Models\StaticPage;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Giới hạn nới theo IP để chống dồn dập request vào form liên hệ.
        // Giới hạn chặt hơn (3 lần/10 phút, 10 lần/ngày) được kiểm tra thủ công
        // trong ContactMessageController, chỉ tính lần gửi thành công.
        RateLimiter::for('contact', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Theme không được tự query DB (xem docs/STANDARDS.md) — nav static
        // page cho layouts.header đến từ composer này, không phải view.
        View::composer('layouts.header', function (ViewInstance $view): void {
            $view->with('navStaticPages', StaticPage::query()->published()->get());
        });
    }
}
