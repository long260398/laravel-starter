<?php

namespace App\Actions;

use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * Đăng ký usage cho `$usable` trên 1 file (đăng ký file vào kho media trước
 * nếu chưa có). Idempotent: gọi lại nhiều lần với cùng disk+path+usable+field
 * không tạo trùng dòng nào (dựa vào unique constraint ở 2 bảng).
 */
class RegisterMediaUsage
{
    public function handle(string $disk, string $path, Model $usable, string $field): void
    {
        $media = app(RegisterMedia::class)->handle($disk, $path);

        MediaUsage::query()->firstOrCreate([
            'media_id' => $media->id,
            'usable_type' => $usable::class,
            'usable_id' => $usable->getKey(),
            'field' => $field,
        ]);
    }
}
