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

    /*
    |--------------------------------------------------------------------------
    | Per-path SEO (server-rendered for / and /download)
    |--------------------------------------------------------------------------
    | Title is combined in views as: "{title} — {APP_NAME}" (keep title ≤ ~50 chars).
    */
    'pages' => [
        'home' => [
            'title' => 'Send files with a pickup code',
            'description' => 'Upload a file (up to 100MB), set expiry and download limit, and share one pickup code. Recipients download in the browser — no account required.',
        ],
        'download' => [
            'title' => 'Download with a pickup code',
            'description' => 'Enter your pickup code to download a file shared with otshare. Simple, timed links with a set number of downloads.',
        ],
    ],

];
