<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\DuplicateSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\EscapingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\PageSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\SeriesSitemapProvider;

function bind_series_provider(int $count): void
{
    app()->instance(SeriesSitemapProvider::class, new SeriesSitemapProvider($count));
    app()->tag(SeriesSitemapProvider::class, SitemapProvider::PROVIDER_TAG);
}

function feature_index_locs(string $content): array
{
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();

    $locs = [];

    foreach ($xml->sitemap as $node) {
        $locs[] = (string) $node->loc;
    }

    return $locs;
}

it('serves a single document as a plain urlset when below the limits', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    bind_series_provider(2);

    $content = $this->get('/sitemap.xml')->assertStatus(200)->assertHeader('Content-Type', 'application/xml')->getContent();

    expect($content)->toContain('<urlset ')
        ->not->toContain('<sitemapindex ');
});

it('becomes a sitemap index when the URL count is exceeded', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    bind_series_provider(3);

    $content = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

    expect($content)->toContain('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->and(feature_index_locs($content))->toBe([
            'https://example.test/sitemap-1.xml',
            'https://example.test/sitemap-2.xml',
        ]);
});

it('serves split documents at deterministic chunk routes', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    bind_series_provider(3);

    $first = $this->get('/sitemap-1.xml')->assertStatus(200)->assertHeader('Content-Type', 'application/xml')->getContent();
    $second = $this->get('/sitemap-2.xml')->assertStatus(200)->getContent();

    $firstLocs = [];
    $secondLocs = [];

    foreach (simplexml_load_string($first)->url as $node) {
        $firstLocs[] = (string) $node->loc;
    }

    foreach (simplexml_load_string($second)->url as $node) {
        $secondLocs[] = (string) $node->loc;
    }

    expect($firstLocs)->toBe(['https://example.test/pages/1', 'https://example.test/pages/2'])
        ->and($secondLocs)->toBe(['https://example.test/pages/3']);
});

it('returns 404 for a chunk that does not exist', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    bind_series_provider(3);

    $this->get('/sitemap-3.xml')->assertStatus(404);
    $this->get('/sitemap-0.xml')->assertStatus(404);
});

it('returns 404 for an empty sitemap chunk beyond the single empty document', function (): void {
    $this->get('/sitemap-2.xml')->assertStatus(404);
    $this->get('/sitemap-1.xml')->assertStatus(200);
});

it('builds index locations from the trusted canonical origin under a poisoned Host header', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);
    config()->set('basekit-laravel-seo.canonical.base_url', 'https://cdn.example.test');

    bind_series_provider(3);

    $content = $this->get('/sitemap.xml', ['Host' => 'attacker.example'])->getContent();

    expect($content)->not->toContain('attacker.example')
        ->and(feature_index_locs($content))->toBe([
            'https://cdn.example.test/sitemap-1.xml',
            'https://cdn.example.test/sitemap-2.xml',
        ]);
});

it('splits by byte size through configuration', function (): void {
    $renderer = app(SitemapRenderer::class);

    $maxBytes = $renderer->scaffoldBytes()
        + $renderer->entryBytes(new SitemapEntry('https://example.test/pages/1'))
        + $renderer->entryBytes(new SitemapEntry('https://example.test/pages/2'));

    config()->set('basekit-laravel-seo.sitemap.max_bytes', $maxBytes);

    bind_series_provider(3);

    $content = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

    expect(feature_index_locs($content))->toBe([
        'https://example.test/sitemap-1.xml',
        'https://example.test/sitemap-2.xml',
    ]);

    $chunk = $this->get('/sitemap-1.xml')->getContent();

    expect(strlen($chunk))->toBeLessThanOrEqual($maxBytes);
});

it('deduplicates before chunking so duplicates never create extra documents', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 3);

    app()->tag([PageSitemapProvider::class, DuplicateSitemapProvider::class], SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

    expect($content)->toContain('<urlset ')
        ->not->toContain('<sitemapindex ')
        ->and($this->get('/sitemap-2.xml')->getStatusCode())->toBe(404);
});

it('responds with an error page when a single entry cannot fit a document', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_bytes', 200);

    app()->instance(EscapingSitemapProvider::class, new EscapingSitemapProvider);
    app()->tag(EscapingSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $this->get('/sitemap.xml')->assertStatus(500);
});
