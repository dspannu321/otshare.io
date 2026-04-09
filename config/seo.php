<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social & brand (optional)
    |--------------------------------------------------------------------------
    | OG image: absolute HTTPS URL to a raster image (1200×630 PNG or JPG).
    | SVG is not reliable for Facebook/LinkedIn previews — add a PNG and set this.
    */
    'og_image' => env('SEO_OG_IMAGE'),
    'og_image_width' => (int) env('SEO_OG_IMAGE_WIDTH', 1200),
    'og_image_height' => (int) env('SEO_OG_IMAGE_HEIGHT', 630),

    /** Without @ — e.g. "otshare" for twitter:site */
    'twitter_handle' => env('SEO_TWITTER_HANDLE'),

    /** Browser UI / PWA hint (matches v2 canvas) */
    'theme_color' => env('SEO_THEME_COLOR', '#060a12'),

    /** Google Search Console — HTML tag method (content value only) */
    'google_site_verification' => env('SEO_GOOGLE_SITE_VERIFICATION'),

    /** Bing Webmaster — optional */
    'ms_validate' => env('SEO_MS_VALIDATE'),

    /** GA4 Measurement ID (loaded only in production after consent) */
    'ga4_measurement_id' => env('GA4_MEASUREMENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Per-path SEO (server-rendered)
    |--------------------------------------------------------------------------
    | Title is combined in views as: "{title} — {APP_NAME}" (keep title ≤ ~50 chars).
    | / = marketing landing, /app = upload tool, /download = unlock page.
    */
    'pages' => [
        'landing' => [
            'title' => 'Share files & text with a pickup code',
            'description' => 'Send up to 100MB without an account: one pickup code, optional link or QR, expiry and download limits. Recipients unlock in the browser.',
        ],
        'app' => [
            'title' => 'Create a share',
            'description' => 'Upload a file or paste text, set expiry and how many unlocks allowed, then share your pickup code or link.',
        ],
        'download' => [
            'title' => 'Unlock with a pickup code',
            'description' => 'Enter your pickup code or open your unlock link to download a file or copy shared text. Timed access with a set number of unlocks.',
        ],
        'privacy' => [
            'title' => 'Privacy',
            'description' => 'How otshare handles data for temporary file and text sharing.',
        ],
        'terms' => [
            'title' => 'Terms',
            'description' => 'Terms of use for otshare.io.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal pages — optional identity / contact (set in .env for production)
    |--------------------------------------------------------------------------
    */
    'legal' => [
        'last_updated' => env('LEGAL_LAST_UPDATED', 'April 8, 2026'),
        'contact_email' => env('LEGAL_CONTACT_EMAIL'),
        'operator_name' => env('LEGAL_OPERATOR_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Landing page aggregates (/ — optional marketing offsets + cache)
    |--------------------------------------------------------------------------
    | Totals are computed from the shares table. Extra_* values add to displayed
    | numbers (e.g. legacy data before metrics). Set cache_seconds to 0 in tests.
    */
    'landing_stats' => [
        'cache_seconds' => (int) env('LANDING_STATS_CACHE_SECONDS', 60),
        'extra_shares' => (int) env('LANDING_STATS_EXTRA_SHARES', 0),
        'extra_bytes' => (int) env('LANDING_STATS_EXTRA_BYTES', 0),
        'extra_unlocks' => (int) env('LANDING_STATS_EXTRA_UNLOCKS', 0),
    ],

];
