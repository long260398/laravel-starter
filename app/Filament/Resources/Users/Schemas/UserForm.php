<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Họ tên')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Vai trò')
                    ->options(UserRole::class)
                    ->required()
                    ->native(false)
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
                Toggle::make('is_active')
                    ->label('Đang hoạt động')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
                TextInput::make('password')
                    ->label('Mật khẩu')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->confirmed()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                TextInput::make('password_confirmation')
                    ->label('Xác nhận mật khẩu')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->maxLength(255),
            ]);
    }
}
