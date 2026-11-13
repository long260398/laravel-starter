<?php

namespace App\Models;

use App\Enums\ContactStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

#[Fillable([
    'name', 'phone', 'email', 'subject', 'message',
    'status', 'admin_note', 'ip_address', 'user_agent',
])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, Prunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
        ];
    }

    /**
     * Tin đã xử lý (status = processed) và không cập nhật trong N tháng gần
     * nhất (`config('contact.prune_after_months')`). Dữ liệu cá nhân nên xóa
     * hẳn sau khi không còn cần dùng tới.
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('status', ContactStatus::Processed)
            ->where('updated_at', '<=', now()->subMonths(config('contact.prune_after_months')));
    }
}
