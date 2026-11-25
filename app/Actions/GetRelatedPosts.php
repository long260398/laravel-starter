<?php

namespace App\Actions;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class GetRelatedPosts
{
    /**
     * Bài liên quan: cùng chuyên mục hoặc chung ít nhất 1 thẻ, chỉ bài đã
     * xuất bản, loại bài hiện tại. Bài không có cả chuyên mục và thẻ trả về
     * rỗng (không fallback sang "bài mới nhất").
     *
     * @return Collection<int, Post>
     */
    public function handle(Post $post, int $limit = 4): Collection
    {
        $tagIds = $post->tags->pluck('id');

        if ($post->category_id === null && $tagIds->isEmpty()) {
            return new Collection;
        }

        return Post::query()
            ->published()
            ->whereKeyNot($post->id)
            ->where(function ($query) use ($post, $tagIds): void {
                if ($post->category_id !== null) {
                    $query->orWhere('category_id', $post->category_id);
                }

                if ($tagIds->isNotEmpty()) {
                    $query->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $tagIds));
                }
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}
