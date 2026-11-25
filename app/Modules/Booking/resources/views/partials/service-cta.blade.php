{{--
    Đăng ký vào slot 'service.show.actions' (xem Services\ServicesServiceProvider /
    show.blade.php). Chỉ dùng $source/$item truyền tường minh qua context của
    <x-module-slot> — không đọc $service hay bất kỳ biến nào khác của view gọi.
--}}
<a href="{{ route('booking.create', ['source' => $source, 'item' => $item]) }}"
    class="mt-6 inline-block rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-surface">
    Đặt lịch dịch vụ này
</a>
