<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply security headers (SOC2 / hardening). No sensitive data in headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), autoplay=(), camera=(), cross-origin-isolated=(), display-capture=(), encrypted-media=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(), usb=(), web-share=(), xr-spatial-tracking=()'
        );

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $csp = $this->contentSecurityPolicy($request);
        if ($csp !== '') {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        if (! config('security.csp_enabled', true)) {
            return '';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "manifest-src 'self'",
            "media-src 'self'",
            "worker-src 'self' blob:",
            "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            'connect-src '.implode(' ', array_unique(array_merge(
                [
                    "'self'",
                    'https://www.google-analytics.com',
                    'https://analytics.google.com',
                    'https://*.google-analytics.com',
                    'https://*.analytics.google.com',
                    'https://www.googletagmanager.com',
                    'https://fonts.googleapis.com',
                    'https://fonts.gstatic.com',
                ],
                config('security.csp_connect_extra', [])
            ))),
        ];

        if ($request->secure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
