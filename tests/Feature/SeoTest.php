<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_robots_txt_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $response->getContent());
        $this->assertStringContainsString('User-agent: *', $response->getContent());
    }

    public function test_sitemap_xml_lists_public_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<loc>'.e(url('/')).'</loc>', $response->getContent());
        $this->assertStringContainsString('<loc>'.e(url('/download')).'</loc>', $response->getContent());
    }

    public function test_home_has_meta_description_and_canonical(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="', false);
        $response->assertSee('<link rel="canonical" href="'.e(url('/')).'">', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('og:title', false);
    }

    public function test_download_page_has_distinct_meta(): void
    {
        $response = $this->get('/download');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.e(url('/download')).'">', false);
        $response->assertSee('Download with a pickup code', false);
    }

    public function test_download_page_with_query_string_has_canonical_without_query(): void
    {
        $response = $this->get('/download?code=AB12-345678');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.e(url('/download')).'">', false);
    }
}
