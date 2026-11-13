<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
