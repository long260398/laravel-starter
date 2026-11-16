<?php

namespace Tests\Concerns;

/**
 * Đặt tạm biến env (putenv + $_ENV + $_SERVER) TRƯỚC khi app boot — cần cho
 * test theme/module: giá trị này ảnh hưởng tới binding/registration lúc
 * boot (ActiveTheme, ModulesServiceProvider...), nên config() sau khi boot
 * là quá muộn, bắt buộc phải đổi env trước `parent::setUp()`.
 *
 * putenv() thay đổi state ở mức tiến trình PHP — SỐNG SÓT qua mọi lần tạo
 * app mới giữa các test (khác với config(), tự reset theo từng app mới) —
 * nên phải khôi phục đúng giá trị gốc (không đoán/hardcode) để không rò rỉ
 * sang test chạy sau, bất kể thứ tự chạy ngẫu nhiên. `tearDownWithTemporaryEnv()`
 * được Laravel tự gọi (xem Illuminate\Foundation\Testing\Concerns\
 * InteractsWithTestCaseLifecycle::setUpTraits(), quy ước tên method theo
 * tên trait) — không cần tự khai `tearDown()` ở từng test.
 */
trait WithTemporaryEnv
{
    /**
     * @var array<string, string|false>
     */
    private array $temporaryEnvOriginals = [];

    /**
     * @param  array<string, string>  $values
     */
    protected function setTemporaryEnv(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $this->temporaryEnvOriginals)) {
                $this->temporaryEnvOriginals[$key] = getenv($key);
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    protected function tearDownWithTemporaryEnv(): void
    {
        foreach ($this->temporaryEnvOriginals as $key => $original) {
            if ($original === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);

                continue;
            }

            putenv("{$key}={$original}");
            $_ENV[$key] = $original;
            $_SERVER[$key] = $original;
        }

        $this->temporaryEnvOriginals = [];
    }
}
