<?php

namespace Tests\Feature;

use App\Models\AdminAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use RefreshDatabase;

    private function baseConfig(): void
    {
        config(['otshare.admin_allowed_ips' => ['127.0.0.1']]);
        config(['otshare.admin_secret' => 'mfa-test-key']);
    }

    public function test_dashboard_redirects_to_totp_setup_when_not_configured(): void
    {
        $this->baseConfig();
        $this->createOpsAdmin('mfa-test-key', 'dashboard-pw', null);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'mfa-test-key']))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.post'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'password' => 'dashboard-pw',
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'mfa-test-key']))
            ->assertRedirect(route('admin.mfa.setup.show', ['key' => 'mfa-test-key']));
    }

    public function test_totp_setup_confirm_then_dashboard(): void
    {
        $this->baseConfig();
        $this->createOpsAdmin('mfa-test-key', 'dashboard-pw', null);
        $g = app(Google2FA::class);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'mfa-test-key']))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.post'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'password' => 'dashboard-pw',
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.mfa.setup.show', ['key' => 'mfa-test-key']))
            ->assertOk()
            ->assertSee('Set up authenticator', false)
            ->assertSee('data:image/svg+xml;base64,', false);

        $secret = session('admin_totp_pending_secret');
        $this->assertIsString($secret);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.mfa.setup.confirm'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'code' => $g->getCurrentOtp($secret),
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'mfa-test-key']))
            ->assertOk()
            ->assertSee('Operations', false);

        $this->assertDatabaseHas('admin_access_logs', [
            'event' => AdminAccessLog::EVENT_TOTP_SETUP_COMPLETE,
        ]);
    }

    public function test_mfa_verify_then_dashboard_when_totp_already_configured(): void
    {
        $this->baseConfig();
        $g = app(Google2FA::class);
        $secret = $g->generateSecretKey();
        $this->createOpsAdmin('mfa-test-key', 'dashboard-pw', $secret);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.mfa.show', ['key' => 'mfa-test-key']))
            ->assertRedirect(route('admin.login.show', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'mfa-test-key']))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.post'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'password' => 'dashboard-pw',
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'mfa-test-key']))
            ->assertRedirect(route('admin.mfa.show', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.mfa.verify'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'code' => $g->getCurrentOtp($secret),
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'mfa-test-key']))
            ->assertOk()
            ->assertSee('Authenticator session', false);
    }

    public function test_logout_clears_session_and_requires_sign_in_again(): void
    {
        $this->baseConfig();
        $g = app(Google2FA::class);
        $secret = $g->generateSecretKey();
        $this->createOpsAdmin('mfa-test-key', 'dashboard-pw', $secret);
        $this->loginOpsAdminFully('mfa-test-key', 'dashboard-pw', $secret);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.logout'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
            ])
            ->assertRedirect(route('admin.login.show', ['key' => 'mfa-test-key']));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.dashboard', ['key' => 'mfa-test-key']))
            ->assertRedirect(route('admin.login.show', ['key' => 'mfa-test-key']));
    }

    public function test_password_failure_is_logged(): void
    {
        $this->baseConfig();
        $this->createOpsAdmin('mfa-test-key', 'dashboard-pw', null);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => 'mfa-test-key']))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->from(route('admin.login.show', ['key' => 'mfa-test-key']))
            ->post(route('admin.login.post'), [
                '_token' => csrf_token(),
                'key' => 'mfa-test-key',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('admin_access_logs', [
            'event' => AdminAccessLog::EVENT_PASSWORD_FAILURE,
        ]);
    }
}
