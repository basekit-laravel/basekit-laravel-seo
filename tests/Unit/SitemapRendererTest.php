<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapDocument;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

function renderer_under_test(): SitemapRenderer
{
    return new SitemapRenderer(new SitemapPaths('/sitemap.xml'));
}

function renderer_locs(string $content): array
{
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();

    $locs = [];

    foreach ($xml->url as $node) {
        $locs[] = (string) $node->loc;
    }

    return $locs;
}

it('renders a urlset with the XML declaration and sitemap namespace', function (): void {
    $xml = renderer_under_test()->urlset([
        new SitemapEntry(loc: 'https://example.test/about'),
    ]);

    expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>'."\n")
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain('<loc>https://example.test/about</loc>');
});

it('renders optional fields only when present and defaults priority to 0.5', function (): void {
    $rendered = renderer_under_test()->urlset([
        new SitemapEntry(loc: 'https://example.test/about', lastmod: '2026-09-21T10:30:00+00:00', changefreq: 'monthly', priority: 0.8),
        new SitemapEntry(loc: 'https://example.test/products/bar', priority: 1.0),
        new SitemapEntry(loc: 'https://example.test/empty'),
    ]);

    expect($rendered)->toContain('<lastmod>2026-09-21T10:30:00+00:00</lastmod>')
        ->toContain('<changefreq>monthly</changefreq>')
        ->toContain('<priority>0.8</priority>')
        ->toContain('<priority>1</priority>')
        ->toContain('<priority>0.5</priority>')
        ->not->toContain('<lastmod></lastmod>')
        ->not->toContain('<changefreq></changefreq>');
});

it('escapes XML-special characters in entry locations', function (): void {
    $xml = renderer_under_test()->urlset([
        new SitemapEntry(loc: 'https://example.test/search?q=a&b=<c>&"d"'),
    ]);

    expect($xml)->toContain('https://example.test/search?q=a&amp;b=&lt;c&gt;&amp;&quot;d&quot;')
        ->and(renderer_locs($xml))->toBe(['https://example.test/search?q=a&b=<c>&"d"']);
});

it('renders a sitemap index from trusted origin and chunk paths', function (): void {
    $catalog = new SitemapCatalog([
        new SitemapDocument(index: 1, count: 2, bytes: 100),
        new SitemapDocument(index: 2, count: 1, bytes: 80),
    ]);

    $xml = renderer_under_test()->index($catalog, rtrim('https://example.com/', '/'));

    $parsed = simplexml_load_string($xml);

    expect($parsed)->not->toBeFalse();

    $locs = [];

    foreach ($parsed->sitemap as $node) {
        $locs[] = (string) $node->loc;
    }

    expect($locs)->toBe([
        'https://example.com/sitemap-1.xml',
        'https://example.com/sitemap-2.xml',
    ]);
});

it('escapes index locations even when the origin contains XML-special characters', function (): void {
    $catalog = new SitemapCatalog([
        new SitemapDocument(index: 1, count: 1, bytes: 80),
    ]);

    $xml = renderer_under_test()->index($catalog, 'https://example.test/a&b');

    expect($xml)->toContain('https://example.test/a&amp;b/sitemap-1.xml');
});

it('omits lastmod from index entries because there is no meaningful date', function (): void {
    $catalog = new SitemapCatalog([
        new SitemapDocument(index: 1, count: 1, bytes: 80),
    ]);

    expect(renderer_under_test()->index($catalog, 'https://example.test'))
        ->not->toContain('<lastmod>');
});

it('accounts bytes so a rendered urlset length equals the sum of its parts', function (): void {
    $renderer = renderer_under_test();
    $entries = [
        new SitemapEntry(loc: 'https://example.test/éclair'),
        new SitemapEntry(loc: 'https://example.test/pages/2', lastmod: '2026-09-21T10:30:00+00:00', priority: 0.5),
    ];

    $expected = $renderer->scaffoldBytes();
    $expected += array_sum(array_map($renderer->entryBytes(...), $entries));

    expect(strlen($renderer->urlset($entries)))->toBe($expected);
});

it('counts multibyte locations in bytes, not characters', function (): void {
    $renderer = renderer_under_test();
    $entry = new SitemapEntry(loc: 'https://example.test/é');

    expect($renderer->entryBytes($entry))->toBe(strlen($renderer->entryFragment($entry)) + 1)
        ->and(strlen('é'))->toBe(2)
        ->and(mb_strlen('é'))->toBe(1);
});
