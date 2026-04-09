<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

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
        $content = $response->getContent();
        $this->assertStringContainsString('<loc>'.e(url('/')).'</loc>', $content);
        $this->assertStringContainsString('<loc>'.e(url('/app')).'</loc>', $content);
        $this->assertStringContainsString('<loc>'.e(url('/download')).'</loc>', $content);
        $this->assertStringContainsString('<loc>'.e(url('/privacy')).'</loc>', $content);
        $this->assertStringContainsString('<loc>'.e(url('/terms')).'</loc>', $content);
    }

    public function test_landing_page_has_meta_description_and_canonical(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="', false);
        $response->assertSee('<link rel="canonical" href="'.e(url('/')).'">', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('og:title', false);
        $response->assertSee('FAQPage', false);
        $response->assertSee('Share files and text without forcing anyone to log in', false);
    }

    public function test_app_page_has_distinct_meta(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.e(url('/app')).'">', false);
        $response->assertSee('Create a share', false);
    }

    public function test_download_page_has_distinct_meta(): void
    {
        $response = $this->get('/download');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.e(url('/download')).'">', false);
        $response->assertSee('Unlock with a pickup code', false);
    }

    public function test_download_page_with_query_string_has_canonical_without_query(): void
    {
        $response = $this->get('/download?code=AB12-345678');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.e(url('/download')).'">', false);
    }

    public function test_llms_txt_lists_key_urls(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $body = $response->getContent();
        $this->assertStringContainsString(url('/'), $body);
        $this->assertStringContainsString(url('/app'), $body);
        $this->assertStringContainsString(url('/download'), $body);
    }
}
