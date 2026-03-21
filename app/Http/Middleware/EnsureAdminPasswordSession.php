<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\AdminCredentialResolver;
use App\Support\Admin\AdminCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPasswordSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $credential = $request->attributes->get('admin_credential');
        if (! $credential instanceof AdminCredential) {
            abort(500);
        }

        if ($request->routeIs('admin.login.show', 'admin.login.post')) {
            return $next($request);
        }

        $admin = Admin::query()->select(['id', 'password_hash'])->find($credential->adminId);
        if ($admin === null) {
            abort(404);
        }

        if ($admin->password_hash === null || $admin->password_hash === '') {
            return response()->view('admin.incomplete-account', [], 503);
        }

        if (! app(AdminCredentialResolver::class)->passwordSessionValid($request, $credential)) {
            $key = $request->header('X-Admin-Key')
                ?? $request->query('key')
                ?? $request->input('key');
            if (! is_string($key) || $key === '') {
                abort(404);
            }

            return redirect()->route('admin.login.show', ['key' => $key]);
        }

        return $next($request);
    }
}
