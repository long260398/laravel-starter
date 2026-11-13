<?php

namespace Tests\Feature;

use App\Mail\NewContactMessageMail;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactMessageMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'a@example.com',
            'subject' => 'Tư vấn dịch vụ',
            'message' => 'Tôi muốn hỏi về dịch vụ.',
            'form_token' => Crypt::encrypt(now()->subSeconds(5)->timestamp),
            'website' => '',
        ], $overrides);
    }

    public function test_mail_failure_still_saves_message_and_logs_error(): void
    {
        Setting::set('contact_email', 'admin@example.com');

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('SMTP down'));

        Log::shouldReceive('error')->once();

        $response = $this->post(route('contact.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_missing_contact_email_setting_skips_sending_and_logs_warning(): void
    {
        Mail::shouldReceive('to')->never();
        Log::shouldReceive('warning')->once();

        $response = $this->post(route('contact.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_reply_to_uses_customer_email_when_present(): void
    {
        Setting::set('contact_email', 'admin@example.com');
        Mail::fake();

        $this->post(route('contact.store'), $this->postData(['email' => 'khach@example.com']));

        Mail::assertSent(
            NewContactMessageMail::class,
            fn (NewContactMessageMail $mail): bool => $mail->hasReplyTo('khach@example.com'),
        );
    }

    public function test_no_reply_to_when_customer_has_no_email(): void
    {
        Setting::set('contact_email', 'admin@example.com');
        Mail::fake();

        $this->post(route('contact.store'), $this->postData(['email' => '']));

        Mail::assertSent(
            NewContactMessageMail::class,
            fn (NewContactMessageMail $mail): bool => $mail->envelope()->replyTo === [],
        );
    }
}
