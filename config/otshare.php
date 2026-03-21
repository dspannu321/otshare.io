<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max file size (bytes)
    |--------------------------------------------------------------------------
    */
    'max_file_size' => env('OTSHARE_MAX_FILE_SIZE', 100 * 1024 * 1024), // 100MB

    /*
    |--------------------------------------------------------------------------
    | Default share expiry (minutes)
    |--------------------------------------------------------------------------
    */
    'default_expiry_minutes' => (int) env('OTSHARE_EXPIRY_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Default max downloads per share
    |--------------------------------------------------------------------------
    */
    'default_max_downloads' => (int) env('OTSHARE_MAX_DOWNLOADS', 1),

    /*
    |--------------------------------------------------------------------------
    | Download token validity (minutes)
    |--------------------------------------------------------------------------
    */
    'token_expiry_minutes' => (int) env('OTSHARE_TOKEN_EXPIRY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Redeem: lock share after N failed attempts
    |--------------------------------------------------------------------------
    */
    'redeem_lock_after_attempts' => (int) env('OTSHARE_REDEEM_LOCK_AFTER', 5),

    /*
    |--------------------------------------------------------------------------
    | Redeem: lock duration (minutes)
    |--------------------------------------------------------------------------
    */
    'redeem_lock_minutes' => (int) env('OTSHARE_REDEEM_LOCK_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Max wrong passcode attempts before file is deleted
    |--------------------------------------------------------------------------
    */
    'max_passcode_attempts' => (int) env('OTSHARE_MAX_PASSCODE_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Pickup code format: short_id length (chars), PIN length (digits)
    |--------------------------------------------------------------------------
    */
    'short_id_length' => 4,
    'pin_length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Storage disk for encrypted blobs (local or s3)
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('OTSHARE_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Security: max JSON size for crypto_meta (bytes)
    |--------------------------------------------------------------------------
    */
    'max_crypto_meta_size' => (int) env('OTSHARE_MAX_CRYPTO_META_SIZE', 4096),

    /*
    |--------------------------------------------------------------------------
    | Rate limits (per minute per IP unless noted)
    |--------------------------------------------------------------------------
    */
    'rate_limit_shares_create' => (int) env('OTSHARE_RATE_LIMIT_SHARES', 30),
    'rate_limit_upload' => (int) env('OTSHARE_RATE_LIMIT_UPLOAD', 20),
    'rate_limit_redeem' => (int) env('OTSHARE_RATE_LIMIT_REDEEM', 15),
    'rate_limit_download' => (int) env('OTSHARE_RATE_LIMIT_DOWNLOAD', 30),
    'rate_limit_confirm' => (int) env('OTSHARE_RATE_LIMIT_CONFIRM', 20),

    /*
    |--------------------------------------------------------------------------
    | Admin panel: secret for hidden dashboard (query ?key= or header X-Admin-Key)
    | Also accepts keys created via `php artisan otshare:admin-create` (admins table).
    |--------------------------------------------------------------------------
    */
    'admin_secret' => env('OTSHARE_ADMIN_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin IP allowlist: comma-separated IPv4/IPv6 or CIDR (e.g. 203.0.113.0/24).
    | Empty: non-production allows any IP; production requires this to be non-empty.
    |--------------------------------------------------------------------------
    */
    'admin_allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('OTSHARE_ADMIN_ALLOWED_IPS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Admin TOTP session lifetime (hours) after successful authenticator check
    |--------------------------------------------------------------------------
    |
    | The admin area uses a separate browser session cookie that expires when the
    | browser closes; this value only applies while that session stays open.
    |
    */
    'admin_mfa_session_hours' => (int) env('OTSHARE_ADMIN_MFA_SESSION_HOURS', 12),

];
