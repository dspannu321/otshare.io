<?php

namespace App\Services;

use App\Models\Admin;
use App\Support\Admin\AdminCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminCredentialResolver
{
    public function resolve(string $providedKey): ?AdminCredential
    {
        $envSecret = config('otshare.admin_secret');
        if (is_string($envSecret) && $envSecret !== '' && ! hash_equals($envSecret, $providedKey)) {
            return null;
        }

        foreach (Admin::query()->cursor() as $admin) {
            if (Hash::check($providedKey, $admin->key_hash)) {
                return AdminCredential::fromAdmin($admin, $this->fingerprint($providedKey));
            }
        }

        return null;
    }

    public function fingerprint(string $providedKey): string
    {
        return hash_hmac('sha256', $providedKey, (string) config('app.key'));
    }

    public function passwordSessionValid(Request $request, AdminCredential $credential): bool
    {
        return $request->session()->get('admin_auth_admin_id') === $credential->adminId
            && $request->session()->get('admin_auth_k_fp') === $credential->fingerprint;
    }

    public function markPasswordVerified(Request $request, AdminCredential $credential): void
    {
        $request->session()->put([
            'admin_auth_admin_id' => $credential->adminId,
            'admin_auth_k_fp' => $credential->fingerprint,
        ]);
    }

    public function mfaSessionValid(Request $request, AdminCredential $credential): bool
    {
        if (! $credential->totpIsConfigured()) {
            return false;
        }

        $exp = (int) $request->session()->get('admin_mfa_expires_at', 0);

        return $request->session()->get('admin_mfa_fp') === $credential->fingerprint
            && $exp > now()->getTimestamp();
    }

    public function markMfaVerified(Request $request, AdminCredential $credential): void
    {
        $hours = max(1, min(168, (int) config('otshare.admin_mfa_session_hours', 12)));
        $request->session()->put([
            'admin_mfa_fp' => $credential->fingerprint,
            'admin_mfa_expires_at' => now()->addHours($hours)->getTimestamp(),
        ]);
    }

    public function totpPendingSecret(Request $request): ?string
    {
        $secret = $request->session()->get('admin_totp_pending_secret');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    public function totpPendingMatches(Request $request, AdminCredential $credential): bool
    {
        return $request->session()->get('admin_totp_pending_fp') === $credential->fingerprint
            && $this->totpPendingSecret($request) !== null;
    }

    public function putTotpPending(Request $request, AdminCredential $credential, string $base32Secret): void
    {
        $request->session()->put([
            'admin_totp_pending_secret' => $base32Secret,
            'admin_totp_pending_fp' => $credential->fingerprint,
        ]);
    }

    public function clearTotpPending(Request $request): void
    {
        $request->session()->forget(['admin_totp_pending_secret', 'admin_totp_pending_fp']);
    }

    public function clearFullAdminSession(Request $request): void
    {
        $request->session()->forget([
            'admin_auth_admin_id',
            'admin_auth_k_fp',
            'admin_mfa_fp',
            'admin_mfa_expires_at',
            'admin_totp_pending_secret',
            'admin_totp_pending_fp',
        ]);
    }
}
