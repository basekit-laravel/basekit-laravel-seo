<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Contracts;

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * Provides sitemap entries for an application or domain.
 *
 * Implementations are discovered through the container tag
 * {@see SitemapProvider::PROVIDER_TAG} and registered by binding the class and
 * tagging it in a service provider:
 *
 *     $this->app->tag(MySitemapProvider::class, SitemapProvider::PROVIDER_TAG);
 *
 * Providers run in registration order and may return any iterable (arrays,
 * generators, collections). The package never interprets the entries' meaning:
 * a provider decides for itself where its URLs come from.
 */
interface SitemapProvider
{
    public const PROVIDER_TAG = 'basekit-laravel-seo.sitemap-providers';

    /**
     * @return iterable<SitemapEntry>
     */
    public function entries(): iterable;
}
