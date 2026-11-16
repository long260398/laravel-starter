<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Post;
use App\Models\StaticPage;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use App\Support\Theme\ThemeViewResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;
use Throwable;

/**
 * Hợp đồng view của theme (docs/THEME-CONTRACT.md):
 *
 * 1. Theme `default` phải có đủ mọi view bắt buộc.
 * 2. Mỗi theme chỉ render view nó TỰ CÓ (không tính view fallback từ
 *    default) bằng đúng dữ liệu mẫu trong hợp đồng — view dùng biến ngoài
 *    hợp đồng làm PHP ném "Undefined variable" (E_WARNING), Laravel chuyển
 *    thành ErrorException, test fail và chỉ rõ theme/view nào.
 * 3. Theme không được chứa view ngoài danh sách cho phép (core + module).
 *
 * Trước khi render, `ThemeViewResolver::apply($theme)` được gọi để finder
 * trỏ đúng theme đang kiểm tra — nhờ vậy view lồng nhau (`layouts.header`,
 * `layouts.footer`...) cũng resolve/fallback đúng như lúc chạy thật, không
 * cần tách riêng theo file vật lý.
 */
class ThemeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_theme_has_every_required_view(): void
    {
        foreach (array_keys($this->coreViewSamples()) as $relativePath) {
            $this->assertFileExists(
                $this->themePath('default', $relativePath),
                "Theme mặc định (default) thiếu view bắt buộc: views/{$relativePath}"
            );
        }
    }

    public function test_each_theme_renders_its_own_contract_views_with_sample_data(): void
    {
        $samples = $this->coreViewSamples();

        foreach ($this->discoverThemes() as $theme) {
            app(ThemeViewResolver::class)->apply($theme);

            foreach ($samples as $relativePath => $data) {
                if (! is_file($this->themePath($theme, $relativePath))) {
                    continue;
                }

                $this->renderOrFail($theme, $relativePath, fn () => view($this->toViewName($relativePath), $data)->render());
            }
        }
    }

    public function test_module_view_overrides_render_with_sample_data_when_present(): void
    {
        $samples = $this->moduleViewSamples();

        // Không theme nào hiện tại ghi đè view module — vẫn cần ít nhất 1
        // assertion để PHPUnit không báo "risky" khi loop dưới không chạy.
        $this->addToAssertionCount(1);

        foreach ($this->discoverThemes() as $theme) {
            app(ThemeViewResolver::class)->apply($theme);

            foreach ($samples as $relativePath => [$logicalView, $data]) {
                if (! is_file($this->themePath($theme, "modules/{$relativePath}"))) {
                    continue;
                }

                $this->renderOrFail($theme, "modules/{$relativePath}", fn () => view($logicalView, $data)->render());
            }
        }
    }

    public function test_theme_does_not_ship_views_outside_the_contract(): void
    {
        $allowedCore = array_keys($this->coreViewSamples());
        $allowedModules = array_keys($this->moduleViewSamples());

        foreach ($this->discoverThemes() as $theme) {
            $viewsRoot = $this->themePath($theme);

            if (! is_dir($viewsRoot)) {
                continue;
            }

            foreach (File::allFiles($viewsRoot) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());

                if (Str::startsWith($relative, 'modules/')) {
                    $this->assertContains(
                        Str::after($relative, 'modules/'),
                        $allowedModules,
                        "Theme [{$theme}] có view module ngoài hợp đồng: views/{$relative}"
                    );

                    continue;
                }

                $this->assertContains(
                    $relative,
                    $allowedCore,
                    "Theme [{$theme}] có view ngoài hợp đồng (docs/THEME-CONTRACT.md): views/{$relative}"
                );
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function discoverThemes(): array
    {
        return collect(File::directories(resource_path('themes')))
            ->map(fn (string $path): string => basename($path))
            ->values()
            ->all();
    }

    private function themePath(string $theme, string $relativeView = ''): string
    {
        return resource_path("themes/{$theme}/views").($relativeView !== '' ? "/{$relativeView}" : '');
    }

    private function toViewName(string $relativePath): string
    {
        return str_replace('/', '.', Str::beforeLast($relativePath, '.blade.php'));
    }

    private function renderOrFail(string $theme, string $relativePath, callable $render): void
    {
        try {
            $render();
        } catch (Throwable $e) {
            $this->fail("Theme [{$theme}], view [views/{$relativePath}] render lỗi: {$e->getMessage()}");
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function coreViewSamples(): array
    {
        $staticPage = StaticPage::factory()->published()->create();
        $post = Post::factory()->published()->create();
        $banners = Banner::factory()->published()->forPosition('home_slider')->count(2)->create();

        return [
            'layouts/app.blade.php' => [],
            'layouts/header.blade.php' => ['navStaticPages' => collect([$staticPage])],
            'layouts/footer.blade.php' => [],
            'home.blade.php' => [
                'sliderBanners' => $banners,
                'middleBanners' => collect(),
                'latestPosts' => collect([$post]),
            ],
            'posts/index.blade.php' => [
                'posts' => Post::query()->published()->paginate(12),
            ],
            'posts/show.blade.php' => ['post' => $post],
            'static-pages/show.blade.php' => ['page' => $staticPage],
            'contact/create.blade.php' => [
                'honeypotField' => 'website',
                'tokenField' => 'form_token',
                'formToken' => 'sample-token',
                // $errors luôn có sẵn khi chạy qua HTTP thật (session middleware tự share),
                // view() trực tiếp trong test không đi qua middleware nên phải tự truyền.
                'errors' => new ViewErrorBag,
            ],
            'errors/404.blade.php' => [],
            'errors/500.blade.php' => [],
            'components/banner-slider.blade.php' => ['banners' => $banners],
            'components/post-card.blade.php' => ['post' => $post],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    private function moduleViewSamples(): array
    {
        $category = ServiceCategory::factory()->published()->create();
        $service = Service::factory()->published()->for($category, 'category')->create();
        $album = GalleryAlbum::factory()->published()->create();

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
            'gallery/index.blade.php' => ['gallery::index', ['albums' => collect([$album])]],
            'gallery/show.blade.php' => ['gallery::show', ['album' => $album]],
            'gallery/nav-item.blade.php' => ['gallery::nav-item', []],
            'gallery/home-section.blade.php' => ['gallery::home-section', ['albums' => collect([$album])]],
            'gallery/components/album-card.blade.php' => ['gallery::components.album-card', ['album' => $album]],
        ];
    }
}
