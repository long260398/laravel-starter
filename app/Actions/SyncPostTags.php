<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Str;

class SyncPostTags
{
    /**
     * Gán danh sách thẻ (theo tên) cho bài viết, tạo thẻ mới nếu tên chưa
     * tồn tại (so trùng theo slug). Đây là nơi duy nhất tạo `Tag` mới — khớp
     * yêu cầu "tạo thẻ mới ngay khi gõ" từ `TagsInput` trên form Post.
     *
     * @param  array<int, string>  $names
     */
    public function handle(Post $post, array $names): void
    {
        $tagIds = collect($names)
            ->map(fn ($name): string => trim((string) $name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique(fn (string $name): string => Str::slug($name))
            ->map(fn (string $name): int => Tag::query()
                ->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])
                ->id)
            ->all();

        $post->tags()->sync($tagIds);
    }
}
