<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * Deliberately re-emits locations already provided by other providers to lock
 * in the deduplication policy: the first occurrence wins.
 */
final class DuplicateSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield new SitemapEntry(
            loc: 'https://example.test/about',
            lastmod: '2026-01-01T00:00:00+00:00',
            priority: 0.1,
        );

        yield new SitemapEntry(
            loc: 'https://example.test/members',
        );
    }
}
