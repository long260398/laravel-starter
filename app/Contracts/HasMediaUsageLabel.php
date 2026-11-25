<?php

namespace App\Contracts;

use App\Models\Media;

/**
 * Implemented bởi mọi model có thể là `usable` của một `MediaUsage`, để trang
 * chi tiết trong Thư viện media hiển thị đúng tên + link tới bản ghi — core
 * (`MediaUsage`) không cần biết tên model/resource cụ thể nào.
 */
interface HasMediaUsageLabel
{
    public function mediaUsageLabel(): string;

    public function mediaUsageUrl(): ?string;

    /**
     * Gỡ field này khỏi model khi Admin xóa cưỡng ép 1 media đang dùng (xem
     * App\Actions\DeleteMedia với $force = true). Mặc định (xem
     * App\Models\Concerns\TracksMediaUsage) đặt field về null — model có cột
     * NOT NULL phải override (Banner chuyển trạng thái Draft, GalleryImage
     * xóa hẳn dòng ảnh). Trả về chuỗi mô tả nếu có điều đáng chú ý cần báo
     * cho Admin (ví dụ bản ghi bị chuyển về Nháp), null nếu không có gì đặc biệt.
     */
    public function releaseMediaOnForceDelete(string $field, Media $media): ?string;
}
