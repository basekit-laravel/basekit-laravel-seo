<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;

it('derives chunk paths from the base sitemap path', function (): void {
    $paths = new SitemapPaths('/sitemap.xml');

    expect($paths->base())->toBe('/sitemap.xml')
        ->and($paths->chunk(1))->toBe('/sitemap-1.xml')
        ->and($paths->chunk(2))->toBe('/sitemap-2.xml')
        ->and($paths->chunk(10))->toBe('/sitemap-10.xml')
        ->and($paths->chunkPattern())->toBe('/sitemap-{n}.xml');
});

it('derives chunk paths from a path in a subdirectory', function (): void {
    $paths = new SitemapPaths('/seo/sitemap.xml');

    expect($paths->chunk(1))->toBe('/seo/sitemap-1.xml')
        ->and($paths->chunkPattern())->toBe('/seo/sitemap-{n}.xml');
});

it('appends chunk numbers when the base path has no xml extension', function (): void {
    $paths = new SitemapPaths('/sitemap');

    expect($paths->chunk(1))->toBe('/sitemap-1.xml')
        ->and($paths->chunkPattern())->toBe('/sitemap-{n}.xml');
});

it('rejects an empty sitemap path', function (): void {
    expect(fn (): SitemapPaths => new SitemapPaths(''))
        ->toThrow(InvalidArgumentException::class);
});
