<?php

namespace Tests;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

abstract class TestCase extends BaseTestCase
{
    protected function createOpsAdmin(string $plainKey, string $password, ?string $totpSecret = null): Admin
    {
        $attrs = [
            'name' => 'ops-test',
            'key_hash' => Hash::make($plainKey),
            'password_hash' => Hash::make($password),
        ];
        if ($totpSecret !== null) {
            $attrs['totp_secret'] = $totpSecret;
        }

        return Admin::create($attrs);
    }

    /**
     * Establishes password + TOTP session cookies for the ops admin (allowlisted IP + matching OTSHARE_ADMIN_SECRET).
     */
    protected function loginOpsAdminFully(string $plainKey, string $password, string $totpBase32): void
    {
        $g = app(Google2FA::class);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.login.show', ['key' => $plainKey]))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.post'), [
                '_token' => csrf_token(),
                'key' => $plainKey,
                'password' => $password,
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => $plainKey]));

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.mfa.verify'), [
                '_token' => csrf_token(),
                'key' => $plainKey,
                'code' => $g->getCurrentOtp($totpBase32),
            ])
            ->assertRedirect(route('admin.dashboard', ['key' => $plainKey]));
    }
}
