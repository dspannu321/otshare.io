<?php

namespace App\Http\Middleware;

use App\Support\SessionCookieName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use a separate session cookie for /admin so closing the browser drops the session.
 * (Tab close alone may not clear cookies until the browser process ends.)
 */
class ConfigureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.cookie' => SessionCookieName::prefixed('otshare_admin_session'),
            'session.expire_on_close' => true,
        ]);

        return $next($request);
    }
}
