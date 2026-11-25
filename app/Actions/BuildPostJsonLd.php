<?php

namespace App\Actions;

use App\Models\Post;

class BuildPostJsonLd
{
    /**
     * Dựng mảng schema.org Article + BreadcrumbList cho trang chi tiết bài
     * viết. Trả về mảng PHP thuần — nơi gọi (view) chỉ `@json(...)` nó,
     * không tự nối chuỗi JSON (giống `App\Modules\Faq\Actions\BuildFaqSchema`).
     *
     * @return array{article: array<string, mixed>, breadcrumb: array<string, mixed>}
     */
    public function handle(Post $post): array
    {
        $url = route('posts.show', $post->slug);

        $breadcrumbItems = [
            ['name' => 'Trang chủ', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('posts.index')],
        ];

        if ($post->category !== null) {
            $breadcrumbItems[] = [
                'name' => $post->category->name,
                'url' => route('posts.category-show', $post->category->slug),
            ];
        }

        $breadcrumbItems[] = ['name' => $post->title, 'url' => $url];

        return [
            'article' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'image' => $post->featured_image !== null ? [public_file_url($post->featured_image)] : [],
                'datePublished' => $post->published_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $url,
                ],
            ],
            'breadcrumb' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($breadcrumbItems)->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])->values()->all(),
            ],
        ];
    }
}
