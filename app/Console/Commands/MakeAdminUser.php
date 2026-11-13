<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:make-admin {--name=} {--email=} {--password=}')]
#[Description('Tạo Quản trị viên đầu tiên, hoặc nâng quyền một người dùng đã tồn tại lên Admin.')]
class MakeAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email');

        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            $existing->forceFill([
                'role' => UserRole::Admin,
                'is_active' => true,
            ])->save();

            $this->info("Đã nâng quyền người dùng [{$existing->email}] thành Quản trị viên.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?? $this->ask('Họ tên');
        $password = $this->option('password') ?? $this->secret('Mật khẩu');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', Password::default()],
            ]
        );

        if ($validator->fails()) {
            $this->error(implode("\n", $validator->errors()->all()));

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info("Đã tạo Quản trị viên mới [{$email}].");

        return self::SUCCESS;
    }
}
