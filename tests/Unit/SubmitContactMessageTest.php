<?php

namespace Tests\Unit;

use App\Actions\SubmitContactMessage;
use App\Exceptions\ContactRateLimitExceeded;
use App\Mail\NewContactMessageMail;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubmitContactMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'a@example.com',
            'subject' => 'Tư vấn dịch vụ',
            'message' => 'Tôi muốn hỏi về dịch vụ.',
        ], $overrides);
    }

    public function test_handle_saves_the_message_with_ip_and_user_agent(): void
    {
        $contactMessage = app(SubmitContactMessage::class)
            ->handle($this->validData(), '1.2.3.4', 'PHPUnit/1.0');

        $this->assertDatabaseHas('contact_messages', [
            'id' => $contactMessage->id,
            'name' => 'Nguyễn Văn A',
            'ip_address' => '1.2.3.4',
            'user_agent' => 'PHPUnit/1.0',
            'status' => 'new',
        ]);
    }

    public function test_handle_throws_when_ip_exceeds_the_ten_minute_limit(): void
    {
        foreach (range(1, 3) as $i) {
            app(SubmitContactMessage::class)->handle($this->validData(['phone' => "09123456{$i}0"]), '1.2.3.4', null);
        }

        $this->expectException(ContactRateLimitExceeded::class);

        app(SubmitContactMessage::class)->handle($this->validData(['phone' => '0912345640']), '1.2.3.4', null);
    }

    public function test_rate_limit_exception_carries_a_positive_retry_after(): void
    {
        foreach (range(1, 3) as $i) {
            app(SubmitContactMessage::class)->handle($this->validData(['phone' => "09123456{$i}0"]), '1.2.3.4', null);
        }

        try {
            app(SubmitContactMessage::class)->handle($this->validData(['phone' => '0912345640']), '1.2.3.4', null);
            $this->fail('Expected ContactRateLimitExceeded was not thrown.');
        } catch (ContactRateLimitExceeded $e) {
            $this->assertGreaterThan(0, $e->retryAfter);
        }
    }

    public function test_handle_does_not_consume_the_rate_limit_for_a_different_ip(): void
    {
        foreach (range(1, 3) as $i) {
            app(SubmitContactMessage::class)->handle($this->validData(['phone' => "09123456{$i}0"]), '1.2.3.4', null);
        }

        $contactMessage = app(SubmitContactMessage::class)
            ->handle($this->validData(['phone' => '0912345650']), '5.6.7.8', null);

        $this->assertDatabaseHas('contact_messages', ['id' => $contactMessage->id]);
    }

    public function test_handle_sends_notification_mail_when_contact_email_is_configured(): void
    {
        Setting::set('contact_email', 'admin@example.com');
        Mail::fake();
        $this->withoutDefer();

        app(SubmitContactMessage::class)->handle($this->validData(), '1.2.3.4', null);

        Mail::assertSent(NewContactMessageMail::class);
    }
}
