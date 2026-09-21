<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * A provider that counts how many times it has been executed, so caching tests
 * can prove that a cached sitemap avoids provider execution without any
 * timing or sleep.
 */
final class CountingSitemapProvider implements SitemapProvider
{
    public static int $executions = 0;

    public function entries(): iterable
    {
        self::$executions++;

        yield new SitemapEntry(loc: 'https://example.test/counted');
    }

    public static function reset(): void
    {
        self::$executions = 0;
    }
}
