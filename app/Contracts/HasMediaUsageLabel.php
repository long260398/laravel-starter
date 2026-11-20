<?php

namespace App\Contracts;

/**
 * Implemented bởi mọi model có thể là `usable` của một `MediaUsage`, để trang
 * chi tiết trong Thư viện media hiển thị đúng tên + link tới bản ghi — core
 * (`MediaUsage`) không cần biết tên model/resource cụ thể nào.
 */
interface HasMediaUsageLabel
{
    public function mediaUsageLabel(): string;

    public function mediaUsageUrl(): ?string;
}
