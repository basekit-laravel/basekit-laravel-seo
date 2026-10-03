<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\SeoData;

/**
 * Records how many times it was invoked so tests can prove that resolution is
 * memoised rather than repeated on every data() call.
 */
final class CountingSeoResolver implements SeoResolver
{
    public static int $calls = 0;

    public function supports(mixed $subject): bool
    {
        return true;
    }

    public function resolve(mixed $subject): SeoData
    {
        self::$calls++;

        return SeoData::make()->withTitle('Resolved');
    }
}
