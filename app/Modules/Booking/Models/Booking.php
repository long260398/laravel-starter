<?php

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Database\Factories\BookingFactory;
use App\Modules\Booking\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

#[Fillable([
    'name', 'phone', 'email', 'service_name',
    'preferred_date', 'time_slot', 'time_slot_label', 'party_size', 'note',
    'status', 'admin_note', 'ip_address', 'user_agent',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, Prunable;

    /**
     * Model nằm ngoài App\Models nên HasFactory không tự đoán được tên factory,
     * phải override thủ công.
     */
    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'preferred_date' => 'date',
            'party_size' => 'integer',
        ];
    }

    /**
     * Xoá theo ngày hẹn (`preferred_date`) đã qua lâu, bất kể trạng thái —
     * lịch cũ quá `config('booking.prune_after_months')` tháng là dữ liệu cá
     * nhân không cần giữ nữa.
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('preferred_date', '<=', now()->subMonths(config('booking.prune_after_months')));
    }
}
