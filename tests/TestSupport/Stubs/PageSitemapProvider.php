<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

final class PageSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield new SitemapEntry(
            loc: 'https://example.test/about',
            lastmod: '2026-09-21T10:30:00+00:00',
            changefreq: 'monthly',
            priority: 0.8,
        );

        yield new SitemapEntry(
            loc: 'https://example.test/contact',
            changefreq: 'yearly',
        );
    }
}
