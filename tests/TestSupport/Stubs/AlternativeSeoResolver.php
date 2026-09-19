<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\SeoData;

/**
 * A second resolver that also supports ContentPage, used to prove the first
 * supporting resolver wins.
 */
final class AlternativeSeoResolver implements SeoResolver
{
    #[\Override]
    public function supports(mixed $subject): bool
    {
        return $subject instanceof ContentPage;
    }

    #[\Override]
    public function resolve(mixed $subject): ?SeoData
    {
        return SeoData::make()->withTitle('Alternative title');
    }
}
