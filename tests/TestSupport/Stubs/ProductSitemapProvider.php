<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

final class ProductSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        return [
            new SitemapEntry(
                loc: 'https://example.test/products/foo',
                lastmod: '2026-09-20T00:00:00+00:00',
            ),
            new SitemapEntry(
                loc: 'https://example.test/products/bar',
                priority: 1.0,
            ),
        ];
    }
}
