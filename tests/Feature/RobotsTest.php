<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsTest extends TestCase
{
    public function test_robots_txt_returns_200_and_blocks_admin_and_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Sitemap:', false);
    }
}
