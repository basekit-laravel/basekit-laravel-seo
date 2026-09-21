<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapAggregator;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\DuplicateSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\EscapingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\FailingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\InvalidSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\PageSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\ProductSitemapProvider;

function phase3_sitemap_locs(string $content): array
{
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();

    $locs = [];

    foreach ($xml->url as $node) {
        $locs[] = (string) $node->loc;
    }

    return $locs;
}

it('serves the sitemap with an XML declaration and the sitemap namespace', function (): void {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml');

    $content = $response->getContent();

    expect($content)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>'."\n")
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
});

it('renders a well-formed urlset when no providers are registered', function (): void {
    $response = $this->get('/sitemap.xml');

    expect($response)->assertStatus(200);

    $xml = simplexml_load_string($response->getContent());

    expect($xml)->not->toBeFalse()
        ->and(count($xml->url))->toBe(0);
});

it('aggregates and renders entries from a single provider', function (): void {
    app()->tag(PageSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

    expect(phase3_sitemap_locs($content))->toBe([
        'https://example.test/about',
        'https://example.test/contact',
    ])->and($content)->toContain('<lastmod>2026-09-21T10:30:00+00:00</lastmod>')
        ->toContain('<changefreq>monthly</changefreq>')
        ->toContain('<priority>0.8</priority>');
});

it('aggregates entries from multiple providers in registration order', function (): void {
    app()->tag([PageSitemapProvider::class, ProductSitemapProvider::class], SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->getContent();

    expect(phase3_sitemap_locs($content))->toBe([
        'https://example.test/about',
        'https://example.test/contact',
        'https://example.test/products/foo',
        'https://example.test/products/bar',
    ])->and(count(phase3_sitemap_locs($content)))->toBe(4);
});

it('is deterministic across requests', function (): void {
    app()->tag([ProductSitemapProvider::class, PageSitemapProvider::class], SitemapProvider::PROVIDER_TAG);

    $first = phase3_sitemap_locs($this->get('/sitemap.xml')->getContent());
    $second = phase3_sitemap_locs($this->get('/sitemap.xml')->getContent());

    expect($first)->toBe($second);
});

it('supports generator-based providers', function (): void {
    app()->tag(ProductSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->getContent();

    expect(phase3_sitemap_locs($content))->toBe([
        'https://example.test/products/foo',
        'https://example.test/products/bar',
    ]);
});

it('deduplicates locations keeping the first occurrence', function (): void {
    app()->tag([PageSitemapProvider::class, DuplicateSitemapProvider::class], SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->getContent();

    expect(phase3_sitemap_locs($content))->toBe([
        'https://example.test/about',
        'https://example.test/contact',
        'https://example.test/members',
    ])->and($content)->toContain('<lastmod>2026-09-21T10:30:00+00:00</lastmod>')
        ->not->toContain('<lastmod>2026-01-01T00:00:00+00:00</lastmod>');
});

it('does not swallow providers that yield invalid entries', function (): void {
    app()->tag(InvalidSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    expect(fn () => app(SitemapAggregator::class)->entries())
        ->toThrow(InvalidArgumentException::class);
});

it('does not swallow provider exceptions', function (): void {
    app()->tag(FailingSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    expect(fn () => app(SitemapAggregator::class)->entries())
        ->toThrow(RuntimeException::class, 'Provider exploded.');
});

it('escapes XML-special characters in provider locations', function (): void {
    app()->tag(EscapingSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->getContent();

    expect($content)->toContain('https://example.test/search?q=a&amp;b=&lt;c&gt;&amp;&quot;d&quot;')
        ->not->toContain('<loc>https://example.test/search?q=a&b=<c>&"d"</loc>')
        ->and(phase3_sitemap_locs($content))->toBe(['https://example.test/search?q=a&b=<c>&"d"']);
});

it('omits optional fields that are not set', function (): void {
    app()->tag(ProductSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml')->getContent();

    expect($content)->toContain('<loc>https://example.test/products/bar</loc>')
        ->not->toContain('<lastmod></lastmod>')
        ->not->toContain('<changefreq></changefreq>')
        ->toContain('<priority>1</priority>');
});

it('never reflects the request Host header in provider-sourced locations', function (): void {
    app()->tag(PageSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $content = $this->get('/sitemap.xml', ['Host' => 'attacker.example'])->getContent();

    expect($content)->not->toContain('attacker.example')
        ->and(phase3_sitemap_locs($content))->toBe([
            'https://example.test/about',
            'https://example.test/contact',
        ]);
});
