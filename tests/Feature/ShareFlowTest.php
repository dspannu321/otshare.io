<?php

namespace Tests\Feature;

use App\Models\Share;
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

    private function futureExpiresIso(int $minutesFromNow = 60): string
    {
        return now()->addMinutes($minutesFromNow)->toIso8601String();
    }

    public function test_create_share_single_step_returns_pickup_code(): void
    {
        $file = UploadedFile::fake()->create('plain.txt', 50, 'text/plain');

        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(90),
            'max_downloads' => 2,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(201);
        $res->assertJsonStructure(['id', 'pickup_code', 'expires_at', 'size_bytes', 'original_name']);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[0-9]{6}$/', $res->json('pickup_code'));

        $share = Share::whereNotNull('object_key')->first();
        $this->assertNotNull($share);
        $this->assertSame(2, $share->max_downloads);
        $this->assertSame([], $share->crypto_meta);
        $this->assertNull($share->kdf);
    }

    public function test_create_share_rejects_max_downloads_above_five(): void
    {
        $file = UploadedFile::fake()->create('a.txt', 10);

        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 6,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors('max_downloads');
    }

    public function test_create_share_rejects_expiry_too_soon(): void
    {
        $file = UploadedFile::fake()->create('a.txt', 10);

        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => now()->addSeconds(30)->toIso8601String(),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors('expires_at');
    }

    public function test_original_name_stored_from_uploaded_filename(): void
    {
        $file = UploadedFile::fake()->create('My Presentation (v2).pptx', 40);

        $res = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(120),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(201);
        $share = Share::whereNotNull('object_key')->latest()->first();
        $this->assertSame('My Presentation (v2).pptx', $share->original_name);
    }

    public function test_redeem_download_confirm_respects_max_downloads(): void
    {
        $file = UploadedFile::fake()->create('doc.txt', 20, 'text/plain');

        $create = $this->post('/api/v1/share', [
            'file' => $file,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 2,
        ], ['Accept' => 'application/json']);

        $create->assertStatus(201);
        $pickup = $create->json('pickup_code');

        $redeem = $this->postJson('/api/v1/redeem', ['pickup_code' => $pickup]);
        $redeem->assertOk();
        $token = $redeem->json('download_token');
        $this->assertNotEmpty($token);

        $dl = $this->get('/api/v1/download?token='.urlencode($token));
        $dl->assertOk();
        $this->assertNotEmpty($dl->headers->get('Content-Disposition'));

        $this->postJson('/api/v1/download/confirm', [
            'token' => $token,
            'success' => true,
        ])->assertOk();

        $share = Share::whereNotNull('object_key')->first();
        $this->assertSame(1, $share->fresh()->download_count);

        $redeem2 = $this->postJson('/api/v1/redeem', ['pickup_code' => $pickup]);
        $redeem2->assertOk();
        $token2 = $redeem2->json('download_token');
        $this->get('/api/v1/download?token='.urlencode($token2))->assertOk();
        $this->postJson('/api/v1/download/confirm', [
            'token' => $token2,
            'success' => true,
        ])->assertOk();

        $this->assertSame(2, $share->fresh()->download_count);

        $this->postJson('/api/v1/redeem', ['pickup_code' => $pickup])
            ->assertStatus(422);
    }

    public function test_create_text_share_redeem_download_confirm(): void
    {
        $body = "Hello,\nthis is a shared note.";

        $create = $this->postJson('/api/v1/share-text', [
            'text' => $body,
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $create->assertStatus(201);
        $pickup = $create->json('pickup_code');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[0-9]{6}$/', $pickup);

        $share = Share::whereNotNull('object_key')->first();
        $this->assertNotNull($share);
        $this->assertStringStartsWith('text/plain', (string) $share->mime);
        $this->assertSame('shared.txt', $share->original_name);

        $redeem = $this->postJson('/api/v1/redeem', ['pickup_code' => $pickup]);
        $redeem->assertOk();
        $token = $redeem->json('download_token');

        $dl = $this->get('/api/v1/download?token='.urlencode($token));
        $dl->assertOk();
        $this->assertSame($body, Storage::disk(config('otshare.storage_disk'))->get($share->object_key));

        $this->postJson('/api/v1/download/confirm', [
            'token' => $token,
            'success' => true,
        ])->assertOk();

        $this->postJson('/api/v1/redeem', ['pickup_code' => $pickup])
            ->assertStatus(422);
    }

    public function test_create_text_share_rejects_empty_text(): void
    {
        $res = $this->postJson('/api/v1/share-text', [
            'text' => "   \n  ",
            'expires_at' => $this->futureExpiresIso(60),
            'max_downloads' => 1,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors('text');
    }
}
