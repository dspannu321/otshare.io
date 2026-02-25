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

];
