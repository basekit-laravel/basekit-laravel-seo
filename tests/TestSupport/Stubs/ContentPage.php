<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

/**
 * A plain, non-Eloquent content object proving resolvers are storage-free.
 */
final class ContentPage
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
    ) {}
}
