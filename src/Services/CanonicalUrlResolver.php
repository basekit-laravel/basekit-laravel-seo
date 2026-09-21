<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the site's trusted canonical origin.
 *
 * This is the package's single source of truth for "what is our site's URL" so
 * the canonical default, the sitemap route and the robots.txt sitemap
 * declaration never trust a raw HTTP Host header by themselves.
 */
final class CanonicalUrlResolver
{
    public function __construct(private readonly Container $container) {}

    /**
     * The trusted origin, or null when no safe base can be established.
     */
    public function resolve(): ?CanonicalUrl
    {
        $baseUrl = config('basekit-laravel-seo.canonical.base_url');
        $base = is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : (string) config('app.url', '');

        if ($base !== '') {
            $canonical = CanonicalUrl::tryFrom($base);

            if ($canonical !== null) {
                return $canonical;
            }
        }

        if (! $this->container->bound('request')) {
            return null;
        }

        $request = $this->container->make('request');

        $host = strtolower((string) $request->getHost());

        $trustedHosts = array_map(
            'strtolower',
            array_values((array) config('basekit-laravel-seo.canonical.trusted_hosts', [])),
        );

        if (! in_array($host, $trustedHosts, true)) {
            return null;
        }

        $scheme = $request->isSecure() ? 'https' : 'http';

        return CanonicalUrl::tryFrom($scheme.'://'.$host);
    }
}
