<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mô phỏng bản export chỉ bán 1 module cho khách (core + đúng 1 module, các
 * module khác không tồn tại trong source lẫn DB) — mỗi module phải tự
 * migrate/rollback được chỉ với core, không cần bất kỳ module nào khác đang
 * có mặt. `tests/TestCase.php` (`migrateFreshUsing()`) luôn nạp migration của
 * MỌI module cho suite chính để tránh phụ thuộc thứ tự chạy test — test này
 * bù lại phần đó bằng cách thử từng module một cách cô lập, trên 1 kết nối
 * sqlite `:memory:` riêng (không đụng tới connection mặc định của suite).
 *
 * Tự quét `app/Modules/*\/Database/Migrations`, không hardcode tên module —
 * thêm module mới tự được test này phủ, không cần sửa file.
 */
class ModuleMigrationIsolationTest extends TestCase
{
    private const string CONNECTION = 'module_isolation_test';

    public function test_each_module_migrates_and_rolls_back_with_only_core_migrations(): void
    {
        $modulePaths = glob(base_path('app/Modules/*/Database/Migrations'), GLOB_ONLYDIR) ?: [];

        $this->assertNotEmpty($modulePaths, 'Không tìm thấy migration module nào để kiểm tra.');

        foreach ($modulePaths as $modulePath) {
            $this->assertModuleMigratesStandaloneWithOnlyCore($modulePath);
        }
    }

    private function assertModuleMigratesStandaloneWithOnlyCore(string $modulePath): void
    {
        $moduleLabel = basename(dirname($modulePath, 2));

        Config::set('database.connections.'.self::CONNECTION, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $paths = [base_path('database/migrations'), $modulePath];

        try {
            $migrateExitCode = $this->artisan('migrate', [
                '--database' => self::CONNECTION,
                '--path' => $paths,
                '--realpath' => true,
                '--force' => true,
            ])->run();

            $this->assertSame(0, $migrateExitCode, "Migrate thất bại cho module [{$moduleLabel}] khi chỉ có core + chính nó.");

            $rollbackExitCode = $this->artisan('migrate:rollback', [
                '--database' => self::CONNECTION,
                '--path' => $paths,
                '--realpath' => true,
                '--force' => true,
            ])->run();

            $this->assertSame(0, $rollbackExitCode, "Rollback thất bại cho module [{$moduleLabel}] khi chỉ có core + chính nó.");
        } finally {
            DB::purge(self::CONNECTION);
        }
    }
}
