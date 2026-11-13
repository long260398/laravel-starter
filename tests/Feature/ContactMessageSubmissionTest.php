<?php

namespace Tests\Feature;

use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ContactMessageSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'a@example.com',
            'subject' => 'Tư vấn dịch vụ',
            'message' => 'Tôi muốn hỏi về dịch vụ.',
        ], $overrides);
    }

    private function freshToken(int $secondsAgo = 5): string
    {
        return Crypt::encrypt(now()->subSeconds($secondsAgo)->timestamp);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return array_merge([
            ...$this->validPayload(),
            'form_token' => $this->freshToken(),
            'website' => '',
        ], $overrides);
    }

    public function test_valid_submission_is_saved(): void
    {
        $response = $this->post(route('contact.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'status' => 'new',
        ]);
    }

    public function test_invalid_submission_is_rejected(): void
    {
        $response = $this->post(route('contact.store'), $this->postData(['name' => '']));

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_filled_is_not_saved_but_returns_success(): void
    {
        $response = $this->post(route('contact.store'), $this->postData(['website' => 'http://spam.example']));

        $response->assertRedirect();
        $response->assertSessionHas('contact_success');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_too_fast_submission_is_blocked(): void
    {
        $response = $this->post(route('contact.store'), $this->postData(['form_token' => $this->freshToken(0)]));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_tampered_token_is_blocked(): void
    {
        $response = $this->post(route('contact.store'), $this->postData(['form_token' => 'khong-hop-le']));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_expired_token_is_blocked(): void
    {
        $response = $this->post(route('contact.store'), $this->postData(['form_token' => $this->freshToken(7201)]));

        $response->assertSessionHasErrors('form');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_strict_rate_limit_blocks_the_fourth_successful_submission_within_ten_minutes(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('contact.store'), $this->postData(['phone' => "09123456{$i}0"]))
                ->assertRedirect();
        }

        $this->assertDatabaseCount('contact_messages', 3);

        $response = $this->post(route('contact.store'), $this->postData(['phone' => '0912345640']));

        $response->assertStatus(429);
        $this->assertDatabaseCount('contact_messages', 3);
    }

    public function test_failed_validations_do_not_count_toward_strict_rate_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('contact.store'), $this->postData(['name' => '']))
                ->assertSessionHasErrors('name');
        }

        $response = $this->post(route('contact.store'), $this->postData());

        $response->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_message_html_is_escaped_in_notification_email(): void
    {
        $contactMessage = ContactMessage::factory()->create([
            'message' => '<script>alert(1)</script>',
        ]);

        $html = (new NewContactMessageMail($contactMessage))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
