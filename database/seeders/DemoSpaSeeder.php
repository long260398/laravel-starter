<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Banner;
use App\Models\Post;
use App\Models\Setting;
use App\Modules\Faq\Models\Faq;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;
use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu demo tiếng Việt cho theme spa-natural (xem docs/themes/spa-natural.md)
 * — spa giả "Hoa Mai Spa". Chạy thủ công: `php artisan db:seed --class=DemoSpaSeeder`.
 * Không có ảnh thật (để trống, theme tự render khối tint) — chỉ chặn chạy
 * ngoài local/testing, không đụng tới dữ liệu production.
 */
class DemoSpaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoSpaSeeder chỉ chạy ở môi trường local/testing.');

            return;
        }

        $this->seedSettings();
        $this->seedBanners();
        $this->seedServices();
        $this->seedTestimonials();
        $this->seedFaqs();
        $this->seedPosts();
    }

    private function seedSettings(): void
    {
        $settings = [
            'site_name' => 'Hoa Mai Spa',
            'slogan' => 'Chăm sóc chậm rãi, cho cơ thể và tâm trí',
            'hotline' => '028 7300 1234',
            'contact_email' => 'lienhe@hoamaispa.vn',
            'address' => '12 Đường Hoa Mai, Phường Thảo Điền, TP. Thủ Đức, TP. Hồ Chí Minh',
            'working_hours' => '09:00 - 20:00 (Thứ 2 - Chủ nhật)',
            'facebook_url' => 'https://facebook.com/hoamaispa',
            'zalo_url' => 'https://zalo.me/0987654321',
            'seo_title' => 'Hoa Mai Spa - Chăm sóc & thư giãn',
            'seo_description' => 'Hoa Mai Spa - không gian thư giãn giữa lòng thành phố với liệu trình massage, chăm sóc da và trị liệu toàn diện.',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }

    private function seedBanners(): void
    {
        Banner::create([
            'title' => 'Chậm lại một chút, cơ thể bạn xứng đáng được lắng nghe',
            'subtitle' => 'Không gian thư giãn giữa lòng thành phố, nơi mỗi liệu trình được chăm chút như một nghi thức.',
            'image' => '',
            'button_text' => 'Đặt lịch ngay',
            'position' => 'home_slider',
            'status' => PublishStatus::Published,
        ]);

        Banner::create([
            'title' => 'Ưu đãi tháng này',
            'subtitle' => 'Giảm 15% cho khách đặt lịch lần đầu qua Zalo.',
            'image' => '',
            'button_text' => 'Nhận ưu đãi',
            'link_url' => route('contact.create', [], false),
            'position' => 'home_middle',
            'status' => PublishStatus::Published,
        ]);
    }

    private function seedServices(): void
    {
        $massage = ServiceCategory::create([
            'name' => 'Massage & Trị liệu',
            'slug' => 'massage-tri-lieu',
            'status' => PublishStatus::Published,
        ]);

        $skincare = ServiceCategory::create([
            'name' => 'Chăm sóc da mặt',
            'slug' => 'cham-soc-da-mat',
            'status' => PublishStatus::Published,
        ]);

        $services = [
            [
                'category' => $massage,
                'name' => 'Massage toàn thân thảo mộc',
                'short_description' => 'Xoa bóp bằng túi thảo mộc ấm, thư giãn toàn thân.',
                'price' => 850000,
                'duration' => '90 phút',
                'is_featured' => true,
            ],
            [
                'category' => $massage,
                'name' => 'Massage đá nóng',
                'short_description' => 'Dùng đá bazan làm nóng để giải phóng cơ bắp căng mỏi.',
                'price' => 750000,
                'duration' => '75 phút',
                'is_featured' => true,
            ],
            [
                'category' => $massage,
                'name' => 'Bấm huyệt chân',
                'short_description' => 'Kích thích huyệt đạo lòng bàn chân, lưu thông khí huyết.',
                'price' => 450000,
                'duration' => '45 phút',
                'is_featured' => true,
            ],
            [
                'category' => $massage,
                'name' => 'Massage cổ vai gáy',
                'short_description' => 'Giảm căng mỏi vùng cổ, vai, gáy cho người làm việc văn phòng.',
                'price' => 400000,
                'duration' => '40 phút',
                'is_featured' => false,
            ],
            [
                'category' => $skincare,
                'name' => 'Chăm sóc da mặt chuyên sâu',
                'short_description' => 'Làm sạch sâu, cấp ẩm và phục hồi da bằng tinh chất thiên nhiên.',
                'price' => 650000,
                'duration' => '60 phút',
                'is_featured' => true,
            ],
            [
                'category' => $skincare,
                'name' => 'Trị liệu mụn',
                'short_description' => 'Lộ trình cá nhân hoá theo tình trạng da từng khách.',
                'price' => null,
                'duration' => '60 phút',
                'is_featured' => false,
            ],
            [
                'category' => $skincare,
                'name' => 'Ủ trắng da toàn thân',
                'short_description' => 'Loại bỏ da chết, cấp ẩm và làm sáng da toàn thân.',
                'price' => 550000,
                'duration' => '50 phút',
                'is_featured' => true,
            ],
            [
                'category' => $skincare,
                'name' => 'Gói dưỡng da cô dâu',
                'short_description' => 'Liệu trình chăm sóc da chuyên sâu trước ngày cưới.',
                'price' => 1800000,
                'duration' => '120 phút',
                'is_featured' => true,
            ],
        ];

        foreach ($services as $data) {
            Service::create([
                'service_category_id' => $data['category']->id,
                'name' => $data['name'],
                'slug' => str($data['name'])->slug(),
                'short_description' => $data['short_description'],
                'content' => $data['short_description'],
                'price' => $data['price'],
                'price_note' => $data['price'] !== null ? 'Từ' : null,
                'duration' => $data['duration'],
                'is_featured' => $data['is_featured'],
                'status' => PublishStatus::Published,
            ]);
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            [
                'customer_name' => 'Minh Anh',
                'customer_title' => 'Khách hàng thân thiết',
                'content' => 'Không gian ở Hoa Mai Spa khiến tôi quên hết mệt mỏi sau mỗi buổi làm. Nhân viên tư vấn rất tận tâm.',
                'rating' => 5,
            ],
            [
                'customer_name' => 'Quốc Bảo',
                'customer_title' => null,
                'content' => 'Dịch vụ massage đá nóng thực sự tuyệt vời, tôi ngủ ngon hơn rất nhiều sau mỗi lần đến đây.',
                'rating' => 5,
            ],
            [
                'customer_name' => 'Thu Hà',
                'customer_title' => 'Freelancer',
                'content' => 'Mình rất thích gói chăm sóc da mặt, da sáng và mềm hơn hẳn sau vài buổi.',
                'rating' => 4,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::create([...$data, 'status' => PublishStatus::Published]);
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'question' => 'Tôi cần đặt lịch trước bao lâu?',
                'answer' => 'Bạn nên đặt lịch trước ít nhất 1 ngày qua Zalo hoặc hotline để được giữ đúng giờ mong muốn.',
            ],
            [
                'question' => 'Hoa Mai Spa có phục vụ khách theo nhóm không?',
                'answer' => 'Có, chúng tôi nhận đặt lịch cho nhóm từ 2 khách trở lên, vui lòng liên hệ trước để được bố trí phòng phù hợp.',
            ],
            [
                'question' => 'Tôi có thể đổi hoặc hủy lịch hẹn không?',
                'answer' => 'Bạn có thể đổi lịch miễn phí nếu báo trước 4 giờ. Hủy sát giờ hẹn có thể mất phí giữ chỗ.',
            ],
            [
                'question' => 'Spa có sản phẩm dành cho da nhạy cảm không?',
                'answer' => 'Có, toàn bộ sản phẩm chăm sóc da tại Hoa Mai Spa đều chiết xuất từ thiên nhiên, phù hợp với da nhạy cảm.',
            ],
            [
                'question' => 'Hoa Mai Spa có hình thức thanh toán nào?',
                'answer' => 'Chúng tôi nhận thanh toán bằng tiền mặt, chuyển khoản và ví điện tử.',
            ],
        ];

        foreach ($faqs as $data) {
            Faq::create([...$data, 'status' => PublishStatus::Published]);
        }
    }

    private function seedPosts(): void
    {
        $posts = [
            [
                'title' => '5 cách thư giãn tại nhà sau một ngày dài',
                'content' => "Sau một ngày làm việc căng thẳng, chỉ vài phút chăm sóc đúng cách cũng giúp cơ thể hồi phục rõ rệt.\n\nThử tắt đèn chính, bật một ngọn nến thơm dịu và ngâm chân với nước ấm pha gừng khoảng 15 phút trước khi ngủ. Kết hợp vài động tác giãn cơ cổ, vai đơn giản cũng giúp giảm đau mỏi do ngồi lâu.\n\nQuan trọng nhất vẫn là lắng nghe cơ thể — nếu cảm thấy quá mệt, một buổi massage thư giãn tại spa sẽ hiệu quả hơn nhiều so với việc tự xoa bóp qua loa ở nhà.",
            ],
            [
                'title' => 'Vì sao massage thảo mộc tốt cho sức khỏe?',
                'content' => "Massage thảo mộc kết hợp hơi ấm và tinh dầu từ các loại lá cây như sả, gừng, ngải cứu — vừa giúp giãn cơ vừa hỗ trợ lưu thông khí huyết.\n\nHơi ấm từ túi thảo mộc giúp giãn mạch máu dưới da, trong khi các động tác xoa bóp đều tay giúp giảm căng cứng ở vùng lưng, vai và cổ.\n\nNhiều khách hàng chia sẻ rằng sau buổi massage thảo mộc, họ ngủ sâu hơn và tinh thần thoải mái hơn trong vài ngày tiếp theo.",
            ],
            [
                'title' => 'Chọn liệu trình chăm sóc da theo từng loại da',
                'content' => "Da dầu, da khô và da hỗn hợp cần những liệu trình chăm sóc khác nhau để đạt hiệu quả tốt nhất.\n\nVới da dầu, nên ưu tiên các liệu trình làm sạch sâu và kiểm soát nhờn. Da khô cần được cấp ẩm nhiều hơn, trong khi da hỗn hợp thường cần kết hợp cả hai nhóm sản phẩm cho từng vùng khác nhau trên mặt.\n\nNếu chưa chắc da mình thuộc loại nào, bạn có thể đến Hoa Mai Spa để được tư vấn và soi da trước khi chọn liệu trình phù hợp.",
            ],
        ];

        foreach ($posts as $data) {
            Post::create([
                'title' => $data['title'],
                'slug' => str($data['title'])->slug(),
                'content' => $data['content'],
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);
        }
    }
}
