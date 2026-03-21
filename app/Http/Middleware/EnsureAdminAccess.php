<?php

namespace App\Http\Middleware;

use App\Services\AdminCredentialResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    /**
     * Validate admin key (env or DB) and attach {@see \App\Support\Admin\AdminCredential} as request attribute "admin_credential".
     */
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-Admin-Key')
            ?? $request->query('key')
            ?? $request->input('key');
        if (! is_string($provided) || $provided === '') {
            abort(404);
        }

        $credential = app(AdminCredentialResolver::class)->resolve($provided);
        if ($credential === null) {
            abort(404);
        }

        $request->attributes->set('admin_credential', $credential);

        return $next($request);
    }
}
