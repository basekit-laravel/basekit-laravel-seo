<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * A provider whose output can change between requests (via a static mode), so
 * tests can prove that clearing the cache regenerates the index and the chunk
 * documents instead of serving stale references.
 */
final class MutableSitemapProvider implements SitemapProvider
{
    public static int $mode = 1;

    public function entries(): iterable
    {
        if (self::$mode === 2) {
            yield new SitemapEntry(loc: 'https://example.test/pages/1');
            yield new SitemapEntry(loc: 'https://example.test/pages/2');
            yield new SitemapEntry(loc: 'https://example.test/pages/3');
        } else {
            yield new SitemapEntry(loc: 'https://example.test/pages/1');
        }
    }

    public static function reset(): void
    {
        self::$mode = 1;
    }
}
