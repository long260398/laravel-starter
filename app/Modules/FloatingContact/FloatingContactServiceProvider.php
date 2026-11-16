<?php

namespace App\Modules\FloatingContact;

use App\Modules\FloatingContact\Support\FloatingContactLinks;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.floating_contact.enabled') = true — không cần tự kiểm tra
 * lại cờ này ở đây, vì provider này không tồn tại trong request khi module
 * tắt. Module không có Model/Migration — toàn bộ tùy chọn lưu qua bảng
 * `settings` sẵn có (key tiền tố `floating_contact.`).
 */
class FloatingContactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'floating_contact');

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerLayoutPartial('body_end', 'floating_contact::widget', fn (): array => [
            'callUrl' => setting('floating_contact.enable_call', false)
                ? FloatingContactLinks::telLink((string) setting('hotline', ''))
                : null,
            'zaloUrl' => setting('floating_contact.enable_zalo', false)
                ? FloatingContactLinks::zaloLink((string) setting('zalo_url', ''))
                : null,
            'messengerUrl' => setting('floating_contact.enable_messenger', false)
                ? FloatingContactLinks::messengerLink((string) setting('messenger_url', ''))
                : null,
            'position' => setting('floating_contact.position', 'right'),
        ]);
    }
}
