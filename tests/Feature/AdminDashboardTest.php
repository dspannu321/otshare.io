<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function allowlistLocal(): void
    {
        config(['otshare.admin_allowed_ips' => ['127.0.0.1']]);
    }

    public function test_admin_login_not_found_without_matching_db_key(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => '']);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'anything']))
            ->assertNotFound();
    }

    public function test_admin_not_found_when_ip_not_allowlisted(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => 'secret']);
        $this->createOpsAdmin('secret', 'dashboard-pw', app(Google2FA::class)->generateSecretKey());

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->get(route('admin.login.show', ['key' => 'secret']))
            ->assertNotFound();
    }

    public function test_admin_dashboard_after_full_auth_with_env_secret(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => 'my-test-secret']);
        $totp = app(Google2FA::class)->generateSecretKey();
        $this->createOpsAdmin('my-test-secret', 'pw', $totp);
        $this->loginOpsAdminFully('my-test-secret', 'pw', $totp);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'my-test-secret']))
            ->assertOk()
            ->assertSee('Operations', false);
    }

    public function test_admin_accepts_x_admin_key_header_after_session(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => 'hdr-secret']);
        $totp = app(Google2FA::class)->generateSecretKey();
        $this->createOpsAdmin('hdr-secret', 'pw', $totp);
        $this->loginOpsAdminFully('hdr-secret', 'pw', $totp);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Admin-Key' => 'hdr-secret'])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_ok_with_db_key_when_env_unpinned(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => '']);
        $totp = app(Google2FA::class)->generateSecretKey();
        $this->createOpsAdmin('cli-plain-key', 'pw', $totp);
        $this->loginOpsAdminFully('cli-plain-key', 'pw', $totp);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'cli-plain-key']))
            ->assertOk()
            ->assertSee('Admin credentials', false);
    }

    public function test_admin_empty_ip_allowlist_allows_any_ip_outside_production(): void
    {
        config(['otshare.admin_allowed_ips' => []]);
        config(['otshare.admin_secret' => 's']);
        $totp = app(Google2FA::class)->generateSecretKey();
        $this->createOpsAdmin('s', 'pw', $totp);
        $this->loginOpsAdminFully('s', 'pw', $totp);

        $this->withServerVariables(['REMOTE_ADDR' => '99.99.99.99'])
            ->get(route('admin.dashboard', ['key' => 's']))
            ->assertOk();
    }

    public function test_dashboard_redirects_to_login_without_password_session(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => 'k']);
        $this->createOpsAdmin('k', 'pw', app(Google2FA::class)->generateSecretKey());

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'k']))
            ->assertRedirect(route('admin.login.show', ['key' => 'k']));
    }

    public function test_env_secret_mismatch_rejects_key(): void
    {
        $this->allowlistLocal();
        config(['otshare.admin_secret' => 'env-key']);
        Admin::create([
            'name' => 'x',
            'key_hash' => Hash::make('db-key'),
            'password_hash' => Hash::make('pw'),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'db-key']))
            ->assertNotFound();
    }
}
