<?php

namespace App\Modules\Testimonials;

use App\Modules\Testimonials\Models\Testimonial;
use App\Modules\Testimonials\Policies\TestimonialPolicy;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.testimonials.enabled') = true — không cần tự kiểm tra lại
 * cờ này ở đây, vì provider này không tồn tại trong request khi module tắt.
 * Module không có route public (chỉ hiện ở section trang chủ).
 */
class TestimonialsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'testimonials');

        Gate::policy(Testimonial::class, TestimonialPolicy::class);

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerHomeSection('testimonials::home-section', fn (): array => [
            'testimonials' => Testimonial::query()->published()->orderBy('sort_order')->take(6)->get(),
        ]);

        $registry->registerThemeViewContract(function (): array {
            $testimonial = Testimonial::factory()->published()->create();

            return [
                'testimonials/home-section.blade.php' => ['testimonials::home-section', [
                    'testimonials' => collect([$testimonial]),
                ]],
            ];
        });
    }
}
