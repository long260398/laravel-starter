<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * `RefreshDatabase` chỉ chạy `migrate:fresh` MỘT LẦN cho cả lượt chạy
     * test (sqlite `:memory:` dùng lại PDO cache giữa các test, xem
     * `Illuminate\Foundation\Testing\RefreshDatabase::refreshTestDatabase()`)
     * — bất kể test nào chạy trước. Nếu test đó tạm tắt 1 module qua
     * `WithTemporaryEnv` (đổi env TRƯỚC `parent::setUp()`), migration của
     * module đó sẽ không được nạp (ServiceProvider không boot), và bảng
     * tương ứng biến mất khỏi DB dùng chung cho SUỐT phần còn lại của lượt
     * chạy — dù `--order-by=random` đặt test đó ở vị trí nào. Luôn cộng
     * thêm đường dẫn migration của MỌI module (bật hay tắt) vào lần
     * migrate:fresh duy nhất này để không phụ thuộc vào thứ tự chạy test.
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing()
    {
        return array_merge(parent::migrateFreshUsing(), [
            '--path' => $this->allModuleMigrationPaths(),
            '--realpath' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function allModuleMigrationPaths(): array
    {
        return glob(base_path('app/Modules/*/Database/Migrations'), GLOB_ONLYDIR) ?: [];
    }
}
