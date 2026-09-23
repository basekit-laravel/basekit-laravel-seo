<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http\Controllers;

use BasekitLaravel\BasekitLaravelSeo\Services\CanonicalUrlResolver;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\Robots;
use Illuminate\Http\Response;

/**
 * Serves robots.txt from the package configuration.
 *
 * A configured `robots.sitemap` is honored verbatim; otherwise the sitemap URL
 * is derived from the trusted canonical origin (never from a raw Host header),
 * keeping it in sync with the package's configured sitemap route.
 */
final class RobotsController
{
    public function __invoke(CanonicalUrlResolver $resolver, SitemapPaths $paths): Response
    {
        $config = (array) config('basekit-laravel-seo.robots', []);

        $sitemap = isset($config['sitemap']) && $config['sitemap'] !== '' ? (string) $config['sitemap'] : null;

        if ($sitemap === null) {
            $origin = $resolver->resolve();

            if ($origin instanceof CanonicalUrl) {
                $sitemap = rtrim($origin->toString(), '/').$paths->base();
            }
        }

        $config['sitemap'] = $sitemap;

        return Robots::make($config)->response();
    }
}
