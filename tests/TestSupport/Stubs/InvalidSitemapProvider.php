<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;

final class InvalidSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield 'this is not a sitemap entry';
    }
}
