<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * Records that it was asked for entries, so a test can prove that aggregation
 * is lazy: a provider must not be touched until the stream reaches it.
 */
final class TrackingSitemapProvider implements SitemapProvider
{
    public static bool $started = false;

    public function __construct(private readonly string $prefix = 'tracked') {}

    public static function reset(): void
    {
        self::$started = false;
    }

    public function entries(): iterable
    {
        self::$started = true;

        yield new SitemapEntry('https://example.test/'.$this->prefix.'/1');
    }
}
