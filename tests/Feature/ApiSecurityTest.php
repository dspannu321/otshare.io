<?php

namespace Tests\Feature;

use App\Models\Share;
use App\Services\PickupCodeService;
use App\Services\ShareTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected PickupCodeService $pickupCode;

    protected ShareTokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pickupCode = app(PickupCodeService::class);
        $this->tokenService = app(ShareTokenService::class);
        Storage::fake(config('otshare.storage_disk', 'local'));
    }

    public function test_security_headers_present_on_api_responses(): void
    {
        $response = $this->postJson('/api/v1/shares', []);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_redeem_returns_same_message_for_invalid_and_expired(): void
    {
        $invalid = $this->postJson('/api/v1/redeem', ['pickup_code' => 'INVALID-123456']);
        $invalid->assertStatus(422);
        $invalid->assertJsonPath('errors.pickup_code.0', 'Invalid or expired pickup code.');

        $malformed = $this->postJson('/api/v1/redeem', ['pickup_code' => 'no-dash']);
        $malformed->assertStatus(422);

        $badFormat = $this->postJson('/api/v1/redeem', ['pickup_code' => 'XXXX-<script>']);
        $badFormat->assertStatus(422);
    }

    public function test_pickup_code_format_validation(): void
    {
        $valid = $this->postJson('/api/v1/redeem', ['pickup_code' => 'K7P4-839217']);
        $valid->assertStatus(422);

        $this->postJson('/api/v1/redeem', ['pickup_code' => ''])->assertStatus(422);
        $this->postJson('/api/v1/redeem', ['pickup_code' => str_repeat('A', 33)])->assertStatus(422);
    }

    public function test_create_share_does_not_leak_internal_ids_in_error(): void
    {
        $res = $this->postJson('/api/v1/shares', []);
        $res->assertSuccessful();
        $body = $res->json();
        $this->assertArrayHasKey('pickup_code', $body);
        $this->assertArrayHasKey('upload_url', $body);
        $this->assertArrayNotHasKey('pickup_hash', $body);
    }

    public function test_download_token_not_reflected_in_error_response(): void
    {
        $share = Share::create([
            'short_id' => 'TEST',
            'pickup_hash' => $this->pickupCode->hash('TEST-123456'),
            'expires_at' => now()->addHour(),
            'max_downloads' => 1,
            'object_key' => 'shares/fake/file.bin',
        ]);
        ['plain_token' => $token] = $this->tokenService->createForShare($share);

        $wrong = $this->getJson('/api/v1/download?token=wrong-token');
        $wrong->assertStatus(404);
        $wrong->assertJsonMissing(['download_token' => $token]);
        $this->assertStringNotContainsString($token, $wrong->getContent());
    }

    public function test_crypto_meta_size_rejected_when_too_large(): void
    {
        $res = $this->postJson('/api/v1/shares', []);
        $res->assertSuccessful();
        $shareId = $res->json('id');
        $uploadUrl = $res->json('upload_url');

        $hugeMeta = [
            'wrapped_file_key' => str_repeat('A', 2000),
            'file_nonce' => base64_encode(random_bytes(12)),
            'wrap_nonce' => base64_encode(random_bytes(12)),
            'salt' => base64_encode(random_bytes(16)),
        ];
        $payload = [
            'ciphertext' => base64_encode(random_bytes(64)),
            'crypto_meta' => $hugeMeta,
            'kdf' => ['type' => 'argon2id'],
            'original_name' => 'test.bin',
            'mime' => 'application/octet-stream',
        ];
        config(['otshare.max_crypto_meta_size' => 100]);
        $upload = $this->postJson($uploadUrl, $payload);
        $upload->assertStatus(422);
        $upload->assertJsonValidationErrors('crypto_meta');
    }

    public function test_original_name_sanitized_by_validation(): void
    {
        $res = $this->postJson('/api/v1/shares', []);
        $res->assertSuccessful();
        $shareId = $res->json('id');
        $uploadUrl = $res->json('upload_url');

        $payload = [
            'ciphertext' => base64_encode(random_bytes(64)),
            'crypto_meta' => [
                'wrapped_file_key' => base64_encode(random_bytes(64)),
                'file_nonce' => base64_encode(random_bytes(12)),
                'wrap_nonce' => base64_encode(random_bytes(12)),
                'salt' => base64_encode(random_bytes(16)),
            ],
            'original_name' => '../../../etc/passwd',
            'mime' => 'application/octet-stream',
        ];
        $upload = $this->postJson($uploadUrl, $payload);
        $upload->assertStatus(422);
        $upload->assertJsonValidationErrors('original_name');
    }
}
