<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaticPageFallbackSlugValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidSlugProvider(): array
    {
        return [
            'php file probe' => ['/wp-login.php'],
            'dotfile probe' => ['/.env'],
            'nested path' => ['/a/b'],
        ];
    }

    #[DataProvider('invalidSlugProvider')]
    public function test_invalid_slug_returns_404_without_querying_static_pages(string $path): void
    {
        $queriesOnStaticPages = 0;

        DB::listen(function ($query) use (&$queriesOnStaticPages): void {
            if (str_contains($query->sql, 'static_pages')) {
                $queriesOnStaticPages++;
            }
        });

        $this->get($path)->assertStatus(404);

        $this->assertSame(0, $queriesOnStaticPages);
    }
}
