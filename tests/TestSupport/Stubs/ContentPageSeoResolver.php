<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\SeoData;
use Override;

final class ContentPageSeoResolver implements SeoResolver
{
    #[Override]
    public function supports(mixed $subject): bool
    {
        return $subject instanceof ContentPage;
    }

    #[Override]
    public function resolve(mixed $subject): ?SeoData
    {
        if (! $this->supports($subject)) {
            return null;
        }

        return SeoData::make()
            ->withTitle($subject->title)
            ->withDescription($subject->description)
            ->withCanonicalUrl($subject->url);
    }
}
