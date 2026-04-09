<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Optional hint file for AI / answer-engine crawlers (not a replacement for HTML content).
     */
    public function llms(): Response
    {
        $site = config('app.name', 'otshare.io');
        $desc = config('seo.pages.landing.description', 'Temporary file and text sharing with pickup codes.');

        $lines = [
            '# '.$site,
            '',
            '> '.$desc,
            '',
            '## Pages',
            '- '.url('/').' — Marketing / overview',
            '- '.url('/app').' — Create a share (upload or text)',
            '- '.url('/download').' — Unlock with a pickup code',
            '- '.url('/privacy').' — Privacy',
            '- '.url('/terms').' — Terms',
            '',
            '## API',
            '- '.url('/api/v1').' — JSON API base (create share, redeem, download)',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => url('/app'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => url('/download'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => url('/privacy'), 'priority' => '0.4', 'changefreq' => 'monthly'],
            ['loc' => url('/terms'), 'priority' => '0.4', 'changefreq' => 'monthly'],
        ];

        $lastmod = now()->toAtomString();

        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $body .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $u) {
            $body .= '  <url>'."\n";
            $body .= '    <loc>'.e($u['loc']).'</loc>'."\n";
            $body .= '    <lastmod>'.e($lastmod).'</lastmod>'."\n";
            $body .= '    <changefreq>'.e($u['changefreq']).'</changefreq>'."\n";
            $body .= '    <priority>'.e($u['priority']).'</priority>'."\n";
            $body .= '  </url>'."\n";
        }

        $body .= '</urlset>';

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
