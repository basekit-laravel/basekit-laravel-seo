<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

final class EscapingSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield new SitemapEntry(
            loc: 'https://example.test/search?q=a&b=<c>&"d"',
        );
    }
}
