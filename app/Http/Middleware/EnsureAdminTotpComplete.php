<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\AdminCredentialResolver;
use App\Support\Admin\AdminCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTotpComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $credential = $request->attributes->get('admin_credential');
        if (! $credential instanceof AdminCredential) {
            abort(500);
        }

        $resolver = app(AdminCredentialResolver::class);

        $key = $request->header('X-Admin-Key')
            ?? $request->query('key')
            ?? $request->input('key');
        if (! is_string($key) || $key === '') {
            abort(404);
        }

        $admin = Admin::query()->select(['id', 'totp_secret'])->find($credential->adminId);
        if ($admin === null) {
            abort(404);
        }

        $totpConfigured = is_string($admin->totp_secret) && $admin->totp_secret !== '';

        if (! $totpConfigured) {
            return redirect()->route('admin.mfa.setup.show', ['key' => $key]);
        }

        $fresh = AdminCredential::fromAdmin($admin, $credential->fingerprint);
        if (! $resolver->mfaSessionValid($request, $fresh)) {
            return redirect()->route('admin.mfa.show', ['key' => $key]);
        }

        return $next($request);
    }
}
