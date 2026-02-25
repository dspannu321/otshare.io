<?php

namespace Tests\Feature;

use App\Models\Share;
use App\Services\PickupCodeService;
use App\Services\ShareTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShareFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('otshare.storage_disk', 'local'));
    }

    public function test_full_share_upload_redeem_download_flow(): void
    {
        $pickup = app(PickupCodeService::class);
        $tokenService = app(ShareTokenService::class);

        $create = $this->postJson('/api/v1/shares', []);
        $create->assertStatus(201);
        $shareId = $create->json('id');
        $pickupCode = $create->json('pickup_code');
        $uploadUrl = $create->json('upload_url');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[0-9]{6}$/', $pickupCode);

        $file = UploadedFile::fake()->create('secret.txt', 100, 'application/octet-stream');
        $cryptoMeta = [
            'wrapped_file_key' => base64_encode(random_bytes(64)),
            'file_nonce' => base64_encode(random_bytes(12)),
            'wrap_nonce' => base64_encode(random_bytes(12)),
            'salt' => base64_encode(random_bytes(16)),
        ];
        $upload = $this->post($uploadUrl, [
            'ciphertext' => $file,
            'crypto_meta' => $cryptoMeta,
            'kdf' => ['type' => 'argon2id'],
            'original_name' => 'secret.txt',
            'mime' => 'text/plain',
        ]);
        $upload->assertSuccessful();

        $redeem = $this->postJson('/api/v1/redeem', ['pickup_code' => $pickupCode]);
        $redeem->assertSuccessful();
        $downloadToken = $redeem->json('download_token');
        $this->assertNotEmpty($downloadToken);

        $download = $this->get('/api/v1/download?token='.urlencode($downloadToken));
        $download->assertSuccessful();

        $confirm = $this->postJson('/api/v1/download/confirm', [
            'token' => $downloadToken,
            'success' => true,
        ]);
        $confirm->assertSuccessful();
    }

    public function test_passcode_failure_exhaustion_deletes_file(): void
    {
        $pickup = app(PickupCodeService::class);
        $tokenService = app(ShareTokenService::class);

        $share = Share::create([
            'short_id' => 'PASS',
            'pickup_hash' => $pickup->hash('PASS-111111'),
            'expires_at' => now()->addHour(),
            'max_downloads' => 1,
        ]);
        $objectKey = 'shares/'.str_replace('-', '', $share->id).'/f.bin';
        Storage::disk(config('otshare.storage_disk'))->put($objectKey, 'ciphertext');
        $share->update(['object_key' => $objectKey]);
        ['plain_token' => $token] = $tokenService->createForShare($share);

        $maxAttempts = config('otshare.max_passcode_attempts', 3);
        for ($i = 0; $i < $maxAttempts; $i++) {
            $res = $this->postJson('/api/v1/download/confirm', [
                'token' => $token,
                'success' => false,
            ]);
            $res->assertSuccessful();
        }
        $res = $this->postJson('/api/v1/download/confirm', [
            'token' => $token,
            'success' => false,
        ]);
        $res->assertSuccessful();
        $res->assertJsonPath('expired', true);
        $res->assertJsonPath('attempts_left', 0);

        $share->refresh();
        $this->assertNull($share->object_key);
        $this->assertTrue($share->expires_at->isPast());
    }
}
