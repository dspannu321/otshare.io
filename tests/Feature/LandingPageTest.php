<?php

namespace Tests\Feature;

use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_live_stats_from_database(): void
    {
        Share::query()->create([
            'short_id' => 'stat01aa',
            'pickup_hash' => str_repeat('a', 64),
            'expires_at' => now()->addDay(),
            'size_bytes' => 2 * 1024 * 1024,
            'download_count' => 2,
        ]);
        Share::query()->create([
            'short_id' => 'stat02bb',
            'pickup_hash' => str_repeat('b', 64),
            'expires_at' => now()->addDay(),
            'size_bytes' => 1 * 1024 * 1024,
            'download_count' => 3,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewHas('landingStats', function (array $stats) {
            return $stats['share_count'] === 2
                && $stats['unlock_count'] === 5
                && $stats['total_bytes'] === 3 * 1024 * 1024;
        });
        $response->assertSee('Shares created', false);
        $response->assertSee('Successful unlocks', false);
        $response->assertSee('Data staged for pickup', false);
        $response->assertSee('What you get', false);
        $response->assertSee('100 MB', false);
    }
}
