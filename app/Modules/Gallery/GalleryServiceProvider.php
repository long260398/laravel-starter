<?php

namespace App\Modules\Gallery;

use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use App\Modules\Gallery\Policies\GalleryAlbumPolicy;
use App\Modules\Gallery\Policies\GalleryImagePolicy;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Chỉ được đăng ký (bởi App\Providers\ModulesServiceProvider) khi
 * config('modules.gallery.enabled') = true — không cần tự kiểm tra lại cờ
 * này ở đây, vì provider này không tồn tại trong request khi module tắt.
 */
class GalleryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'gallery');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        Gate::policy(GalleryAlbum::class, GalleryAlbumPolicy::class);
        Gate::policy(GalleryImage::class, GalleryImagePolicy::class);

        $registry = $this->app->make(ModuleRegistry::class);

        $registry->registerNavItem('gallery::nav-item');

        $registry->registerHomeSection('gallery::home-section', fn (): array => [
            'albums' => GalleryAlbum::query()->published()->orderBy('sort_order')->take(6)->get(),
        ]);

        $registry->registerSitemapUrls(fn () => GalleryAlbum::query()->published()->get()->map(
            fn (GalleryAlbum $album): array => [
                'loc' => route('gallery.show', $album->slug),
                'lastmod' => $album->updated_at,
            ],
        ));

        $registry->registerThemeViewContract(function (): array {
            $album = GalleryAlbum::factory()->published()->create();

            return [
                'gallery/index.blade.php' => ['gallery::index', ['albums' => collect([$album])]],
                'gallery/show.blade.php' => ['gallery::show', ['album' => $album]],
                'gallery/nav-item.blade.php' => ['gallery::nav-item', []],
                'gallery/home-section.blade.php' => ['gallery::home-section', ['albums' => collect([$album])]],
                'gallery/components/album-card.blade.php' => ['gallery::components.album-card', ['album' => $album]],
            ];
        });
    }
}
