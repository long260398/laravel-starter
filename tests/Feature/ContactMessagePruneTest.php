<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactMessagePruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_old_processed_messages_and_keeps_others(): void
    {
        $oldProcessed = ContactMessage::factory()->processed()->create();
        DB::table('contact_messages')->where('id', $oldProcessed->id)->update([
            'updated_at' => now()->subMonths(13),
        ]);

        $recentProcessed = ContactMessage::factory()->processed()->create();
        $newMessage = ContactMessage::factory()->create();

        $this->artisan('model:prune', ['--model' => [ContactMessage::class]])->assertExitCode(0);

        $this->assertDatabaseMissing('contact_messages', ['id' => $oldProcessed->id]);
        $this->assertDatabaseHas('contact_messages', ['id' => $recentProcessed->id]);
        $this->assertDatabaseHas('contact_messages', ['id' => $newMessage->id]);
    }

    public function test_prune_keeps_unprocessed_messages_even_if_old(): void
    {
        $oldButUnprocessed = ContactMessage::factory()->processing()->create();
        DB::table('contact_messages')->where('id', $oldButUnprocessed->id)->update([
            'updated_at' => now()->subMonths(24),
        ]);

        $this->artisan('model:prune', ['--model' => [ContactMessage::class]]);

        $this->assertDatabaseHas('contact_messages', ['id' => $oldButUnprocessed->id]);
    }
}
