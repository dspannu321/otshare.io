<?php

namespace Tests\Feature;

use App\Models\AdminAccessLog;
use App\Models\Share;
use App\Models\ShareToken;
use App\Services\SharePurgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PurgeSharesTest extends TestCase
{
    use RefreshDatabase;

    private function adminConfig(): void
    {
        config(['otshare.admin_allowed_ips' => ['127.0.0.1']]);
        config(['otshare.admin_secret' => 'purge-test-secret']);
    }

    private function authenticateAdminForPurge(): void
    {
        $totp = app(Google2FA::class)->generateSecretKey();
        $this->createOpsAdmin('purge-test-secret', 'purge-dashboard-pw', $totp);
        $this->loginOpsAdminFully('purge-test-secret', 'purge-dashboard-pw', $totp);
    }

    public function test_purge_all_service_deletes_shares_tokens_and_files(): void
    {
        Storage::fake('local');
        config(['otshare.storage_disk' => 'local']);

        $id = (string) Str::uuid();
        $path = 'shares/'.$id.'/file.bin';
        Share::forceCreate([
            'id' => $id,
            'short_id' => 'AB12',
            'pickup_hash' => str_repeat('b', 64),
            'expires_at' => now()->addHour(),
            'object_key' => $path,
            'crypto_meta' => [],
        ]);
        Storage::disk('local')->put($path, 'hello');

        ShareToken::create([
            'id' => (string) Str::uuid(),
            'share_id' => $id,
            'token_hash' => str_repeat('c', 64),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = app(SharePurgeService::class)->purgeAll();

        $this->assertSame(1, $result['shares_deleted']);
        $this->assertSame(1, $result['files_deleted']);
        $this->assertSame([], $result['file_errors']);
        $this->assertSame(0, Share::count());
        $this->assertSame(0, ShareToken::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_artisan_purge_all_force(): void
    {
        Storage::fake('local');
        config(['otshare.storage_disk' => 'local']);

        Share::forceCreate([
            'id' => (string) Str::uuid(),
            'short_id' => 'ZZ99',
            'pickup_hash' => str_repeat('d', 64),
            'expires_at' => now()->addHour(),
            'object_key' => null,
            'crypto_meta' => [],
        ]);

        $this->artisan('otshare:purge-all', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Share::count());
    }

    public function test_admin_purge_post_requires_confirmation_phrase(): void
    {
        $this->adminConfig();
        $this->authenticateAdminForPurge();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'purge-test-secret']))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.purge'), [
                '_token' => csrf_token(),
                'key' => 'purge-test-secret',
                'confirm' => 'wrong',
            ])
            ->assertSessionHasErrors('confirm');
    }

    public function test_admin_purge_post_succeeds(): void
    {
        Storage::fake('local');
        config(['otshare.storage_disk' => 'local']);
        $this->adminConfig();
        $this->authenticateAdminForPurge();

        $id = (string) Str::uuid();
        Share::forceCreate([
            'id' => $id,
            'short_id' => 'PQ77',
            'pickup_hash' => str_repeat('e', 64),
            'expires_at' => now()->addHour(),
            'object_key' => null,
            'crypto_meta' => [],
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'purge-test-secret']))
            ->assertOk();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.purge'), [
                '_token' => csrf_token(),
                'key' => 'purge-test-secret',
                'confirm' => SharePurgeService::CONFIRMATION_PHRASE,
            ]);

        $response->assertRedirect(route('admin.dashboard', ['key' => 'purge-test-secret']));
        $response->assertSessionHas('purge_status');
        $this->assertSame(0, Share::count());
    }

    public function test_share_purge_does_not_delete_admin_access_logs(): void
    {
        Storage::fake('local');
        config(['otshare.storage_disk' => 'local']);
        $this->adminConfig();
        $this->authenticateAdminForPurge();

        Share::forceCreate([
            'id' => (string) Str::uuid(),
            'short_id' => 'LG01',
            'pickup_hash' => str_repeat('f', 64),
            'expires_at' => now()->addHour(),
            'object_key' => null,
            'crypto_meta' => [],
        ]);

        $logsBefore = AdminAccessLog::count();
        $this->assertGreaterThan(0, $logsBefore);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.purge'), [
                '_token' => csrf_token(),
                'key' => 'purge-test-secret',
                'confirm' => SharePurgeService::CONFIRMATION_PHRASE,
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'purge-test-secret']));

        $this->assertSame($logsBefore, AdminAccessLog::count());
    }
}
