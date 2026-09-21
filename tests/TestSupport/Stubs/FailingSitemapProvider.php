<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use RuntimeException;

final class FailingSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield from [];
        throw new RuntimeException('Provider exploded.');
    }
}
