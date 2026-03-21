<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAccessLog;
use App\Models\Share;
use App\Models\ShareToken;
use App\Services\AdminActivityLogger;
use App\Services\AdminCredentialResolver;
use App\Services\SharePurgeService;
use App\Support\Admin\AdminCredential;
use App\Support\Admin\AdminTotpQrCode;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class AdminController extends Controller
{
    public function showLogin(Request $request, AdminCredentialResolver $resolver): View|RedirectResponse
    {
        $key = $request->query('key');
        if (! is_string($key) || $key === '') {
            return view('admin.mfa-missing-key');
        }

        $credential = $resolver->resolve($key);
        if ($credential === null) {
            abort(404);
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null || $admin->password_hash === null || $admin->password_hash === '') {
            return response()->view('admin.incomplete-account', [], 503);
        }

        if ($resolver->passwordSessionValid($request, $credential)) {
            return redirect()->route('admin.dashboard', ['key' => $key]);
        }

        return view('admin.login', ['key' => $key]);
    }

    public function login(Request $request, AdminCredentialResolver $resolver): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credential = $resolver->resolve($request->input('key'));
        if ($credential === null) {
            abort(404);
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null || $admin->password_hash === null || $admin->password_hash === '') {
            abort(503);
        }

        if (! Hash::check($request->input('password'), $admin->password_hash)) {
            AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_PASSWORD_FAILURE);

            return back()->withErrors(['password' => 'Invalid password.'])->withInput($request->only('key'));
        }

        AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_PASSWORD_SUCCESS);
        $resolver->markPasswordVerified($request, $credential);

        return redirect()->route('admin.dashboard', ['key' => $request->input('key')]);
    }

    public function logout(Request $request, AdminCredentialResolver $resolver): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $credential = $resolver->resolve($request->input('key'));
        if ($credential !== null) {
            AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_LOGOUT);
        }

        $resolver->clearFullAdminSession($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.show', ['key' => $request->input('key')]);
    }

    public function showTotpSetup(Request $request, AdminCredentialResolver $resolver, Google2FA $google2fa): View|RedirectResponse
    {
        $key = $request->query('key');
        if (! is_string($key) || $key === '') {
            return view('admin.mfa-missing-key');
        }

        $credential = $resolver->resolve($key);
        if ($credential === null) {
            abort(404);
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null) {
            abort(404);
        }

        if (is_string($admin->totp_secret) && $admin->totp_secret !== '') {
            return redirect()->route('admin.mfa.show', ['key' => $key]);
        }

        if (! $resolver->totpPendingMatches($request, $credential)) {
            $resolver->putTotpPending($request, $credential, $google2fa->generateSecretKey());
        }

        $secret = $resolver->totpPendingSecret($request);
        if ($secret === null) {
            abort(500);
        }

        $issuer = (string) config('app.name', 'otshare');
        $otpauthUrl = $google2fa->getQRCodeUrl($issuer, $admin->name, $secret);

        return view('admin.mfa-setup', [
            'key' => $key,
            'otpauthUrl' => $otpauthUrl,
            'totpQrSrc' => AdminTotpQrCode::imageDataUri($otpauthUrl),
            'secretPlain' => $secret,
            'adminName' => $admin->name,
        ]);
    }

    public function confirmTotpSetup(Request $request, AdminCredentialResolver $resolver, Google2FA $google2fa): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $credential = $resolver->resolve($request->input('key'));
        if ($credential === null) {
            abort(404);
        }

        $pending = $resolver->totpPendingSecret($request);
        if ($pending === null || ! $resolver->totpPendingMatches($request, $credential)) {
            return back()->withErrors(['code' => 'Setup expired or invalid. Open the setup page again.'])->withInput($request->only('key'));
        }

        if (! $google2fa->verifyKey($pending, $request->input('code'), 2)) {
            AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_TOTP_FAILURE);

            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput($request->only('key'));
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null) {
            abort(404);
        }

        $admin->totp_secret = $pending;
        $admin->save();

        $resolver->clearTotpPending($request);

        $fresh = AdminCredential::fromAdmin($admin->fresh(), $credential->fingerprint);
        $resolver->markMfaVerified($request, $fresh);

        AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_TOTP_SETUP_COMPLETE);

        return redirect()->route('admin.dashboard', ['key' => $request->input('key')]);
    }

    public function showMfa(Request $request, AdminCredentialResolver $resolver): View|RedirectResponse
    {
        $key = $request->query('key');
        if (! is_string($key) || $key === '') {
            return view('admin.mfa-missing-key');
        }

        $credential = $resolver->resolve($key);
        if ($credential === null) {
            abort(404);
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null) {
            abort(404);
        }

        if (! is_string($admin->totp_secret) || $admin->totp_secret === '') {
            return redirect()->route('admin.mfa.setup.show', ['key' => $key]);
        }

        $withTotp = AdminCredential::fromAdmin($admin, $credential->fingerprint);
        if ($resolver->mfaSessionValid($request, $withTotp)) {
            return redirect()->route('admin.dashboard', ['key' => $key]);
        }

        return view('admin.mfa', [
            'key' => $key,
            'mfaSessionHours' => (int) config('otshare.admin_mfa_session_hours', 12),
        ]);
    }

    public function verifyMfa(Request $request, AdminCredentialResolver $resolver, Google2FA $google2fa): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $credential = $resolver->resolve($request->input('key'));
        if ($credential === null) {
            abort(404);
        }

        $admin = Admin::query()->find($credential->adminId);
        if ($admin === null || ! is_string($admin->totp_secret) || $admin->totp_secret === '') {
            abort(404);
        }

        $withTotp = AdminCredential::fromAdmin($admin, $credential->fingerprint);

        if (! $google2fa->verifyKey($admin->totp_secret, $request->input('code'), 2)) {
            AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_TOTP_FAILURE);

            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput($request->only('key'));
        }

        AdminActivityLogger::record($request, $credential->adminId, AdminAccessLog::EVENT_TOTP_SUCCESS);
        $resolver->markMfaVerified($request, $withTotp);

        return redirect()->route('admin.dashboard', ['key' => $request->input('key')]);
    }

    /**
     * Hidden admin dashboard: platform metrics (no PII).
     * Access: IP allowlist + key + password session + TOTP setup + TOTP session.
     */
    public function index(Request $request, AdminCredentialResolver $resolver): View
    {
        $credential = $request->attributes->get('admin_credential');
        if (! $credential instanceof AdminCredential) {
            abort(500);
        }

        $now = now();
        $sharesTotal = Share::count();
        $sharesWithFile = Share::whereNotNull('object_key')->count();
        $sharesLast24h = Share::where('created_at', '>=', $now->copy()->subDay())->count();
        $sharesLast7d = Share::where('created_at', '>=', $now->copy()->subDays(7))->count();
        $activeShares = Share::whereNotNull('object_key')
            ->where('expires_at', '>', $now)
            ->whereColumn('download_count', '<', 'max_downloads')
            ->count();

        $totalDownloads = Share::sum('download_count');
        $totalStorageBytes = Share::whereNotNull('object_key')->sum('size_bytes');
        $failedRedeemAttempts = Share::sum('failed_attempts');
        $passcodeFailures = Share::sum('passcode_failed_attempts');
        $lockedNow = Share::whereNotNull('locked_until')->where('locked_until', '>', $now)->count();
        $expired = Share::where('expires_at', '<', $now)->count();
        $tokensCreated = ShareToken::count();
        $tokensUsed = ShareToken::whereNotNull('used_at')->count();
        $tokensValid = ShareToken::whereNull('used_at')->where('expires_at', '>', $now)->count();
        $adminCredentialsCount = Admin::count();
        $adminAccounts = Admin::orderByDesc('created_at')->get(['id', 'name', 'created_at']);

        $recentShares = Share::orderByDesc('created_at')
            ->take(50)
            ->get(['id', 'short_id', 'created_at', 'object_key', 'download_count', 'expires_at', 'max_downloads', 'size_bytes']);

        $disk = config('otshare.storage_disk', 'local');
        $storageRoot = config('filesystems.disks.'.$disk.'.root') ?? storage_path('app/private');
        $diskFreeBytes = @disk_free_space(is_string($storageRoot) && is_dir($storageRoot) ? $storageRoot : storage_path('app'));

        $admin = Admin::query()->find($credential->adminId);
        $withTotp = $admin !== null
            ? AdminCredential::fromAdmin($admin, $credential->fingerprint)
            : $credential;

        $mfaSessionActive = $withTotp->totpIsConfigured()
            && $resolver->mfaSessionValid($request, $withTotp);
        $mfaExpiresAt = null;
        if ($mfaSessionActive) {
            $ts = (int) $request->session()->get('admin_mfa_expires_at', 0);
            if ($ts > 0) {
                $mfaExpiresAt = \Carbon\Carbon::createFromTimestamp($ts)->timezone(config('app.timezone'));
            }
        }

        $adminAccessLogs = AdminAccessLog::query()
            ->where('admin_id', $credential->adminId)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.dashboard', [
            'sharesTotal' => $sharesTotal,
            'sharesWithFile' => $sharesWithFile,
            'sharesLast24h' => $sharesLast24h,
            'sharesLast7d' => $sharesLast7d,
            'activeShares' => $activeShares,
            'totalDownloads' => $totalDownloads,
            'totalStorageBytes' => $totalStorageBytes,
            'failedRedeemAttempts' => $failedRedeemAttempts,
            'passcodeFailures' => $passcodeFailures,
            'lockedNow' => $lockedNow,
            'expired' => $expired,
            'tokensCreated' => $tokensCreated,
            'tokensUsed' => $tokensUsed,
            'tokensValid' => $tokensValid,
            'adminCredentialsCount' => $adminCredentialsCount,
            'adminAccounts' => $adminAccounts,
            'recentShares' => $recentShares,
            'appEnv' => app()->environment(),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'dbDriver' => config('database.default'),
            'dbConnectionName' => (string) config('database.connections.'.config('database.default').'.driver', 'unknown'),
            'cacheStore' => config('cache.default'),
            'queueConnection' => config('queue.default'),
            'sessionDriver' => config('session.driver'),
            'otshareStorageDisk' => $disk,
            'diskFreeBytes' => is_int($diskFreeBytes) || is_float($diskFreeBytes) ? (int) $diskFreeBytes : null,
            'purgeConfirmPhrase' => SharePurgeService::CONFIRMATION_PHRASE,
            'mfaSessionActive' => $mfaSessionActive,
            'mfaExpiresAt' => $mfaExpiresAt,
            'adminAccessLogs' => $adminAccessLogs,
        ]);
    }

    public function purge(Request $request, SharePurgeService $purge): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'string', Rule::in([SharePurgeService::CONFIRMATION_PHRASE])],
            'key' => ['required', 'string'],
        ]);

        $result = $purge->purgeAll();

        $notice = "Purged {$result['shares_deleted']} share(s), removed {$result['files_deleted']} file(s) from storage.";
        if ($result['file_errors'] !== []) {
            $notice .= ' Warnings: '.implode('; ', $result['file_errors']);
        }

        $key = $request->input('key');
        $query = is_string($key) && $key !== '' ? ['key' => $key] : [];

        return redirect()
            ->route('admin.dashboard', $query)
            ->with('purge_status', $notice)
            ->with('purge_warnings', $result['file_errors']);
    }
}
