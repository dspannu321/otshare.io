<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_unknown_route_returns_themed_404(): void
    {
        $response = $this->get('/__no_such_route_otshare__');

        $response->assertNotFound();
        $response->assertSee('404', false);
        $response->assertSee('This page doesn’t exist', false);
        $response->assertSee('v2-shell', false);
    }
}
