<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * A provider that generates a given number of sequential URLs, for exercising
 * splitting and byte limits with a predictable dataset.
 */
final class SeriesSitemapProvider implements SitemapProvider
{
    /** @var list<SitemapEntry> */
    private array $entries;

    public function __construct(int $count)
    {
        $entries = [];

        for ($i = 1; $i <= $count; $i++) {
            $entries[] = new SitemapEntry(loc: 'https://example.test/pages/'.$i);
        }

        $this->entries = $entries;
    }

    public function entries(): iterable
    {
        return $this->entries;
    }
}
