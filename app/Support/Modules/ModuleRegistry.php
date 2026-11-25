<?php

namespace App\Support\Modules;

use Closure;
use Illuminate\Support\Collection;

/**
 * Điểm cắm duy nhất giữa core và module: module tự đăng ký view+data (không
 * phải HTML đã render — core không bao giờ `{!! !!}` nội dung module), core
 * chỉ `@include($item['view'], $item['data'])` mà không cần biết tên module.
 *
 * Singleton — xem App\Providers\AppServiceProvider::register().
 */
class ModuleRegistry
{
    /**
     * @var array<int, array{view: string, data: array<string, mixed>|Closure, priority: int}>
     */
    private array $navItems = [];

    /**
     * @var array<int, array{view: string, data: array<string, mixed>|Closure, priority: int}>
     */
    private array $homeSections = [];

    /**
     * @var array<string, array<int, array{view: string, data: array<string, mixed>|Closure, priority: int}>>
     */
    private array $layoutPartials = [];

    /**
     * @var array<int, callable(): iterable<array{loc: string, lastmod: mixed}>>
     */
    private array $sitemapProviders = [];

    /**
     * @var array<int, callable(): array<string, array{0: string, 1: array<string, mixed>}>>
     */
    private array $themeViewContractProviders = [];

    /**
     * @var array<string, array{label: string, provider: callable(): array<int, array{slug: string, label: string}>}>
     */
    private array $bookableSources = [];

    /**
     * @param  array<string, mixed>|Closure  $data
     */
    public function registerNavItem(string $view, array|Closure $data = [], int $priority = 0): void
    {
        $this->navItems[] = ['view' => $view, 'data' => $data, 'priority' => $priority];
    }

    /**
     * @param  array<string, mixed>|Closure  $data
     */
    public function registerHomeSection(string $view, array|Closure $data = [], int $priority = 0): void
    {
        $this->homeSections[] = ['view' => $view, 'data' => $data, 'priority' => $priority];
    }

    /**
     * @param  array<string, mixed>|Closure  $data
     */
    public function registerLayoutPartial(string $slot, string $view, array|Closure $data = [], int $priority = 0): void
    {
        $this->layoutPartials[$slot][] = ['view' => $view, 'data' => $data, 'priority' => $priority];
    }

    /**
     * @param  callable(): iterable<array{loc: string, lastmod: mixed}>  $provider
     */
    public function registerSitemapUrls(callable $provider): void
    {
        $this->sitemapProviders[] = $provider;
    }

    /**
     * Module tự khai báo hợp đồng view theme có thể override được: theme cho
     * đường dẫn view (vd `services/home-section.blade.php`) và dữ liệu mẫu để
     * `ThemeContractTest` render thử. Core (`docs/THEME-CONTRACT.md` mục 1,
     * `ThemeContractTest`) không còn hardcode danh sách này theo tên module.
     *
     * @param  callable(): array<string, array{0: string, 1: array<string, mixed>}>  $provider
     */
    public function registerThemeViewContract(callable $provider): void
    {
        $this->themeViewContractProviders[] = $provider;
    }

    /**
     * Nguồn nội dung "đặt lịch được" — module nào có nội dung muốn cho đặt
     * lịch (dịch vụ, sau này có thể là tour, lớp học...) tự đăng ký 1 nguồn
     * dưới 1 khóa do chính nó chọn. Module tiêu thụ (ví dụ đặt lịch) chỉ lặp
     * {@see bookableSources()} theo khóa/nhãn chung, không cần biết tên
     * module nào đã đăng ký — module đó tắt thì khóa tương ứng biến mất khỏi
     * kết quả, không lỗi.
     *
     * @param  callable(): array<int, array{slug: string, label: string}>  $provider
     */
    public function registerBookableSource(string $key, string $label, callable $provider): void
    {
        $this->bookableSources[$key] = ['label' => $label, 'provider' => $provider];
    }

    /**
     * @return array<string, array{label: string, items: array<int, array{slug: string, label: string}>}>
     */
    public function bookableSources(): array
    {
        return collect($this->bookableSources)
            ->map(fn (array $entry): array => ['label' => $entry['label'], 'items' => ($entry['provider'])()])
            ->all();
    }

    /**
     * @return array<int, array{view: string, data: array<string, mixed>}>
     */
    public function navItems(): array
    {
        return $this->resolved($this->navItems);
    }

    /**
     * @return array<int, array{view: string, data: array<string, mixed>}>
     */
    public function homeSections(): array
    {
        return $this->resolved($this->homeSections);
    }

    /**
     * @return array<int, array{view: string, data: array<string, mixed>}>
     */
    public function layoutPartials(string $slot): array
    {
        return $this->resolved($this->layoutPartials[$slot] ?? []);
    }

    public function sitemapUrls(): Collection
    {
        return collect($this->sitemapProviders)->flatMap(fn (callable $provider) => $provider());
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public function themeViewContracts(): array
    {
        return collect($this->themeViewContractProviders)
            ->map(fn (callable $provider): array => $provider())
            ->reduce(fn (array $carry, array $item): array => $carry + $item, []);
    }

    /**
     * @param  array<int, array{view: string, data: array<string, mixed>|Closure, priority: int}>  $entries
     * @return array<int, array{view: string, data: array<string, mixed>}>
     */
    private function resolved(array $entries): array
    {
        return collect($entries)
            ->sortBy('priority')
            ->map(fn (array $entry): array => [
                'view' => $entry['view'],
                'data' => $entry['data'] instanceof Closure ? $entry['data']() : $entry['data'],
            ])
            ->values()
            ->all();
    }
}
