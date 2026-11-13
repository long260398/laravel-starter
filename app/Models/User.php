<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RuntimeException;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if (! $user->isDirty(['role', 'is_active'])) {
                return;
            }

            if ($user->role === UserRole::Admin && $user->is_active) {
                return;
            }

            $wasActiveAdmin = $user->getOriginal('role') === UserRole::Admin
                && (bool) $user->getOriginal('is_active');

            if (! $wasActiveAdmin) {
                return;
            }

            if (! static::hasOtherActiveAdmin($user)) {
                throw new RuntimeException('Hệ thống phải còn ít nhất một Quản trị viên đang hoạt động.');
            }
        });

        static::deleting(function (User $user): void {
            if ($user->role !== UserRole::Admin || ! $user->is_active) {
                return;
            }

            if (! static::hasOtherActiveAdmin($user)) {
                throw new RuntimeException('Không thể xóa: hệ thống phải còn ít nhất một Quản trị viên đang hoạt động.');
            }
        });
    }

    private static function hasOtherActiveAdmin(User $user): bool
    {
        return static::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }
}
