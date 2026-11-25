<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\StaticPage;
use App\Models\Tag;
use App\Support\Modules\ModuleRegistry;
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
 *
 * Danh sách view module override được (mục 2 của hợp đồng) KHÔNG hardcode ở
 * đây — mỗi module tự khai báo qua `ModuleRegistry::registerThemeViewContract()`
 * trong `boot()` (xem `ServicesServiceProvider`, `GalleryServiceProvider`,
 * `FloatingContactServiceProvider`). Test này chỉ đọc lại qua
 * `module_registry()->themeViewContracts()`.
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

    public function test_layouts_app_renders_body_end_slot_for_every_theme(): void
    {
        app(ModuleRegistry::class)->registerLayoutPartial('body_end', 'floating_contact::widget', [
            'callUrl' => 'tel:0909123456',
            'zaloUrl' => null,
            'messengerUrl' => null,
            'position' => 'right',
        ]);

        foreach ($this->discoverThemes() as $theme) {
            app(ThemeViewResolver::class)->apply($theme);

            $html = '';

            $this->renderOrFail($theme, 'layouts/app.blade.php', function () use (&$html): void {
                $html = view('layouts.app')->render();
            });

            $this->assertStringContainsString(
                'aria-label="Gọi điện"',
                $html,
                "Theme [{$theme}] không render slot body_end ở layouts.app (thiếu module_registry()->layoutPartials('body_end'))."
            );
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

    public function test_fake_theme_overriding_a_declared_module_view_contract_passes(): void
    {
        $theme = '_fixture_declared';

        $this->makeFixtureThemeView(
            $theme,
            'modules/testimonials/home-section.blade.php',
            '<p>{{ $testimonials->count() }}</p>',
        );

        try {
            $samples = $this->moduleViewSamples();

            $this->assertArrayHasKey(
                'testimonials/home-section.blade.php',
                $samples,
                'Module Testimonials chưa khai báo registerThemeViewContract() cho home-section.'
            );

            app(ThemeViewResolver::class)->apply($theme);

            [$view, $data] = $samples['testimonials/home-section.blade.php'];

            $this->renderOrFail($theme, 'modules/testimonials/home-section.blade.php', fn () => view($view, $data)->render());

            $this->assertContains(
                'testimonials/home-section.blade.php',
                array_keys($samples),
                'View module đã khai báo hợp đồng phải được coi là hợp lệ để theme override.'
            );
        } finally {
            $this->removeFixtureTheme($theme);
        }
    }

    public function test_fake_theme_overriding_an_undeclared_module_view_fails_contract(): void
    {
        $theme = '_fixture_undeclared';

        $this->makeFixtureThemeView(
            $theme,
            'modules/testimonials/not-declared.blade.php',
            '<p>không hợp lệ</p>',
        );

        try {
            $allowedModules = array_keys($this->moduleViewSamples());

            $this->assertNotContains(
                'testimonials/not-declared.blade.php',
                $allowedModules,
                'View module chưa được module khai báo qua registerThemeViewContract() không được coi là hợp lệ.'
            );
        } finally {
            $this->removeFixtureTheme($theme);
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
        $category = PostCategory::factory()->published()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->published()->for($category, 'category')->create();
        $post->tags()->attach($tag);
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
            'posts/show.blade.php' => [
                'post' => $post->refresh()->load(['category', 'tags']),
                'tocHtml' => $post->safe_content,
                'tocItems' => [],
                'relatedPosts' => collect(),
                'articleJsonLd' => [],
                'breadcrumbJsonLd' => [],
            ],
            'posts/category.blade.php' => [
                'category' => $category,
                'posts' => Post::query()->published()->where('category_id', $category->id)->paginate(12),
            ],
            'posts/tag.blade.php' => [
                'tag' => $tag,
                'posts' => Post::query()->published()->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))->paginate(12),
                'noindex' => false,
            ],
            'posts/search.blade.php' => [
                'query' => 'test',
                'posts' => Post::query()->published()->paginate(12),
                'noindex' => true,
            ],
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
     * Nguồn sự thật là chính các module (`ModuleRegistry::registerThemeViewContract()`),
     * không hardcode ở test — thêm module mới không cần sửa file này.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    private function moduleViewSamples(): array
    {
        return app(ModuleRegistry::class)->themeViewContracts();
    }

    /**
     * Tạo 1 view tạm trong `resources/themes/{$theme}/views/{$relativePath}` để
     * kiểm chứng cơ chế hợp đồng tự khai báo — tự xóa theme này ở cuối test
     * (xem `removeFixtureTheme()`), không phải theme thật của dự án.
     */
    private function makeFixtureThemeView(string $theme, string $relativePath, string $contents): void
    {
        $path = $this->themePath($theme, $relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    private function removeFixtureTheme(string $theme): void
    {
        File::deleteDirectory(resource_path("themes/{$theme}"));
    }
}
