<?php

namespace Tests\Feature;

use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('otshare.storage_disk', 'local'));
    }

    private function futureExpiresIso(int $minutesFromNow = 60): string
    {
        return now()->addMinutes($minutesFromNow)->toIso8601String();
    }

    public function test_security_headers_present_on_api_responses(): void
    {
        $file = UploadedFile::fake()->create('x.txt', 10);
        $response = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_create_share_does_not_leak_internal_secrets(): void
    {
        $file = UploadedFile::fake()->create('x.txt', 10);
        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $res->assertSuccessful();
        $body = $res->json();
        $this->assertArrayHasKey('pickup_code', $body);
        $this->assertArrayNotHasKey('pickup_hash', $body);
    }

    public function test_original_name_sanitized_rejects_path_traversal(): void
    {
        $stub = UploadedFile::fake()->create('stub.bin', 64, 'application/octet-stream');
        $file = new UploadedFile(
            $stub->getRealPath(),
            '../../../etc/passwd',
            'application/octet-stream',
            null,
            true
        );

        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $res->assertSuccessful();
        $share = Share::whereNotNull('object_key')->latest()->first();
        $this->assertNotNull($share);
        $this->assertSame('passwd', $share->original_name);
    }
}
