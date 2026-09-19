<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;

it('renders a well-formed XML sitemap urlset', function (): void {
    $sitemap = new Sitemap;

    $content = $sitemap->response([
        ['loc' => 'https://example.test/', 'priority' => '1.0'],
        ['loc' => 'https://example.test/work/project', 'lastmod' => '2026-01-02T00:00:00+00:00', 'priority' => '0.8'],
    ])->getContent();

    expect($content)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain('<loc>https://example.test/</loc>')
        ->toContain('<lastmod>2026-01-02T00:00:00+00:00</lastmod>')
        ->toContain('<priority>0.8</priority>');
});

it('falls back to a default priority and omits optional fields when absent', function (): void {
    $content = (new Sitemap)->response([
        ['loc' => 'https://example.test/about'],
    ])->getContent();

    expect($content)->toContain('<priority>0.5</priority>')
        ->not->toContain('<lastmod>')
        ->not->toContain('<changefreq>');
});

it('returns an application/xml response', function (): void {
    $response = (new Sitemap)->response([['loc' => 'https://example.test/']]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('application/xml');
});

it('escapes XML-special characters in entries', function (): void {
    $content = (new Sitemap)->response([
        ['loc' => 'https://example.test/?a=1&b=2', 'lastmod' => '2026-01-02'],
        ['loc' => 'https://example.test/<title>', 'changefreq' => '"daily" & \'monthly\''],
    ])->getContent();

    expect($content)->toContain('&amp;')
        ->not->toContain('&b=2')
        ->toContain('&lt;title&gt;')
        ->toContain('&quot;daily&quot; &amp; &#039;monthly&#039;');
});

it('strips disallowed XML control characters from entries', function (): void {
    $content = (new Sitemap)->response([
        ['loc' => "https://example.test/\x00page", 'lastmod' => "2026-01-02\x0B"],
    ])->getContent();

    expect($content)->toContain('<loc>https://example.test/page</loc>')
        ->toContain('<lastmod>2026-01-02</lastmod>');
});

it('rejects entries without a non-empty loc', function (): void {
    expect(fn () => (new Sitemap)->response([['priority' => '1.0']]))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Sitemap)->response([['loc' => '']]))
        ->toThrow(InvalidArgumentException::class);
});
