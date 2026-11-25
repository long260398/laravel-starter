<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Policies\BookingPolicy;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.booking.enabled') = true — không cần tự kiểm tra lại cờ
 * này ở đây. Toàn bộ cấu hình/limiter/lịch prune của Booking tự khai báo
 * trong chính provider này, không đụng tới file core nào (AppServiceProvider,
 * routes/console.php) — xoá app/Modules/Booking + entry trong
 * config/modules.php là sạch hoàn toàn.
 */
class BookingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/booking.php', 'booking');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'booking');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        Gate::policy(Booking::class, BookingPolicy::class);

        // Giới hạn nới theo IP, giống `contact` — giới hạn chặt hơn (3 lần/10
        // phút, 10 lần/ngày) được kiểm tra thủ công trong SubmitBookingRequest,
        // chỉ tính lần gửi thành công.
        RateLimiter::for('booking', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('model:prune', ['--model' => [Booking::class]])->daily();
        });

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerNavItem('booking::nav-item');

        $registry->registerHomeSection('booking::home-section');

        $registry->registerSitemapUrls(fn () => [
            ['loc' => route('booking.create'), 'lastmod' => now()],
        ]);

        // Nút CTA trên trang chi tiết của bất kỳ nội dung "đặt lịch được" nào
        // đã tự mở slot riêng của nó (ví dụ Services mở 'service.show.actions').
        // Không có data tĩnh — $source/$item do chính slot truyền qua context.
        $registry->registerLayoutPartial('service.show.actions', 'booking::partials.service-cta');

        // Nút trong widget liên hệ nổi (slot chung, không riêng cho Booking).
        $registry->registerLayoutPartial('floating_contact.extra_buttons', 'booking::partials.floating-button');

        $registry->registerThemeViewContract(fn (): array => [
            'booking/create.blade.php' => ['booking::create', [
                'bookableSources' => [],
                'prefill' => null,
                'timeSlots' => config('booking.time_slots'),
                'minDate' => now()->toDateString(),
                'maxDate' => now()->addDays((int) config('booking.max_advance_days'))->toDateString(),
                'showEmailField' => true,
                'honeypotField' => 'website',
                'tokenField' => 'form_token',
                'formToken' => 'sample-token',
                'errors' => new ViewErrorBag,
            ]],
            'booking/home-section.blade.php' => ['booking::home-section', []],
        ]);
    }
}
