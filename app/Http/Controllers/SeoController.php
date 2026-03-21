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

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => url('/download'), 'priority' => '0.9', 'changefreq' => 'weekly'],
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
