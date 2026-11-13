<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_sample_admin_in_testing_environment(): void
    {
        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_it_creates_no_user_outside_local_and_testing_environments(): void
    {
        $this->app['env'] = 'production';

        (new DatabaseSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertDatabaseCount('users', 0);
    }
}
