<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy
    |--------------------------------------------------------------------------
    | Sent on HTML (web + admin) and API responses. Disable locally if Vite HMR
    | is blocked (set SECURITY_CSP_ENABLED=false in .env).
    */
    'csp_enabled' => filter_var(env('SECURITY_CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /** Optional extra connect-src origins (comma-separated), e.g. custom analytics */
    'csp_connect_extra' => array_values(array_filter(array_map('trim', explode(',', (string) env('SECURITY_CSP_CONNECT_EXTRA', ''))))),

];
