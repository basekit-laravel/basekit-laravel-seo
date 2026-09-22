<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the SEO feature
    |--------------------------------------------------------------------------
    |
    | This package provides structured-data builders, robots directives and XML
    | sitemap rendering. When disabled the service is still resolvable but the
    | package's own conveniences (such as automatic sitemap/robots routes) are
    | not registered.
    |
    */

    'enabled' => (bool) env('BASEKIT_SEO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Site defaults
    |--------------------------------------------------------------------------
    |
    | These values are used as fallbacks when a page or content type provides
    | no explicit metadata. Individual pages override the title/description;
    | the schema builders resolve the rest from here.
    |
    */

    'defaults' => [
        'site_name' => env('BASEKIT_SITE_NAME', 'Basekit'),
        'title_suffix' => '',
        'description' => '',
        'og_image' => null,
        'twitter_card' => 'summary_large_image',
        'locale' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical URLs
    |--------------------------------------------------------------------------
    |
    | Default canonical URL resolution. `base_url` takes precedence over the
    | application's `app.url`. When neither is set, the request host is only
    | used to build a canonical URL if it appears in `trusted_hosts`; otherwise
    | no canonical URL is invented.
    |
    */

    'canonical' => [
        'base_url' => env('BASEKIT_SEO_CANONICAL_URL'),
        'trusted_hosts' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots
    |--------------------------------------------------------------------------
    |
    | Default directives emitted by the Robots builder for the site's
    | robots.txt. `disallow` is a list of URL paths to disallow. The `sitemap`
    | declaration, when left null, is derived from the trusted canonical origin
    | (see `canonical`) plus the package's `/sitemap.xml` route path.
    |
    */

    'robots' => [
        'user_agents' => ['*'],
        'allow' => ['/'],
        'disallow' => [],
        'sitemap' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap scalability
    |--------------------------------------------------------------------------
    |
    | `path` is the route the sitemap is served at; when splitting is required
    | the additional documents are served at `<path minus .xml>-{n}.xml`
    | (e.g. /sitemap-1.xml for the default path).
    |
    | `max_urls` and `max_bytes` are the document limits copied from the
    | standard sitemap format (50,000 URLs / 50MB by default). Aggregate output
    | is split at entry boundaries — never at an arbitrary byte position — so a
    | single document is emitted while its URL count stays at or below
    | `max_urls` and its rendered XML stays at or below `max_bytes`.
    |
    | `cache` holds the aggregated and split documents in the Laravel cache so
    | providers are not re-executed on every request. `store` accepts a cache
    | store name (null = the application's default store); `ttl` is in seconds.
    | Clear the caches with `app(SitemapCache::class)->clear()`.
    |
    */

    'sitemap' => [
        'path' => env('BASEKIT_SEO_SITEMAP_PATH', '/sitemap.xml'),
        'max_urls' => 50_000,
        'max_bytes' => 50 * 1024 * 1024,
        'cache' => [
            'enabled' => (bool) env('BASEKIT_SEO_SITEMAP_CACHE', true),
            'ttl' => 3600,
            'store' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View configuration
    |--------------------------------------------------------------------------
    |
    | The head view rendered by the `<x-basekit-laravel-seo::head />` component.
    | Themes can override it by publishing the package views (see README) or by
    | pointing this key at their own Blade template.
    |
    */

    'views' => [
        'head' => 'basekit-laravel-seo::components.head',
    ],

];
