<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http\Controllers;

use BasekitLaravel\BasekitLaravelSeo\Services\CanonicalUrlResolver;
use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;
use BasekitLaravel\BasekitLaravelSeo\Support\Robots;
use Illuminate\Http\Response;

/**
 * Serves robots.txt from the package configuration.
 *
 * A configured `robots.sitemap` is honored verbatim; otherwise the sitemap URL
 * is derived from the trusted canonical origin (never from a raw Host header),
 * keeping it in sync with the package's sitemap route.
 */
final class RobotsController
{
    public function __invoke(CanonicalUrlResolver $resolver): Response
    {
        $config = (array) config('basekit-laravel-seo.robots', []);

        $sitemap = isset($config['sitemap']) && $config['sitemap'] !== '' ? (string) $config['sitemap'] : null;

        if ($sitemap === null) {
            $origin = $resolver->resolve();

            if ($origin !== null) {
                $sitemap = rtrim($origin->toString(), '/').Sitemap::ROUTE_PATH;
            }
        }

        $config['sitemap'] = $sitemap;

        return Robots::make($config)->response();
    }
}
