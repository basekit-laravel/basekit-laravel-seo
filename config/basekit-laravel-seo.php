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
        'locale' => 'en',
        'url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots
    |--------------------------------------------------------------------------
    |
    | Default directives emitted by the Robots builder for the site's
    | robots.txt. `disallow` is a list of URL paths to disallow.
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
    | View configuration
    |--------------------------------------------------------------------------
    |
    | The default views rendered by the package. Themes can override these by
    | publishing the package views (see README) or by pointing the view names
    | at their own Blade templates. When the `enabled` flag is on, the sitemap
    | and robots routes use these views.
    |
    */

    'views' => [
        'sitemap' => 'seo.sitemap',
    ],

];
