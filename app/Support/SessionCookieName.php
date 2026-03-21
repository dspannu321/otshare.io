<?php

namespace App\Support;

use function blank;

/**
 * Apply RFC 6265bis cookie name prefixes for scanners / browser hardening.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#cookie_prefixes
 */
final class SessionCookieName
{
    public static function prefixed(string $baseName): string
    {
        if ($baseName === '') {
            return $baseName;
        }

        if (str_starts_with($baseName, '__Host-') || str_starts_with($baseName, '__Secure-')) {
            return $baseName;
        }

        if (! filter_var(config('session.cookie_host_prefix', false), FILTER_VALIDATE_BOOLEAN)) {
            return $baseName;
        }

        $path = (string) config('session.path', '/');
        $domain = config('session.domain');

        $pathIsRoot = $path === '/' || $path === '';
        $domainIsEmpty = blank($domain);

        if ($pathIsRoot && $domainIsEmpty) {
            return '__Host-'.$baseName;
        }

        return '__Secure-'.$baseName;
    }
}
