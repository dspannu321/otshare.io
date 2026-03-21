<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAllowedIp
{
    /**
     * Restrict /admin to IPs listed in OTSHARE_ADMIN_ALLOWED_IPS.
     * Empty list: allow any IP outside production; in production, deny (404).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('otshare.admin_allowed_ips', []);

        if ($allowed === []) {
            if (app()->isProduction()) {
                abort(404);
            }

            return $next($request);
        }

        $ip = $request->ip();
        if ($ip === null || $ip === '') {
            abort(404);
        }

        foreach ($allowed as $rule) {
            if ($rule === '') {
                continue;
            }
            if (str_contains($rule, '/')) {
                if (IpUtils::checkIp($ip, $rule)) {
                    return $next($request);
                }
            } elseif (hash_equals($rule, $ip)) {
                return $next($request);
            }
        }

        abort(404);
    }
}
