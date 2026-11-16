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
