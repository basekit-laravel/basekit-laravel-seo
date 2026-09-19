<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Renders an XML sitemap (urlset) from a list of URL descriptors.
 *
 * Each URL is an array with a `loc` plus optional `lastmod`, `changefreq` and
 * `priority`. The sitemap view lives in this package's view namespace so the
 * consuming site can override it by publishing the views.
 */
class Sitemap
{
    /**
     * The published sitemap view name, configurable via the package config.
     */
    protected string $view;

    public function __construct(?string $view = null)
    {
        $this->view = $view ?? config('basekit-laravel-seo.views.sitemap', 'seo.sitemap');
    }

    /**
     * @param  array<int, array<string, mixed>>  $urls
     */
    public function view(array $urls): View
    {
        return view('basekit-laravel-seo::'.$this->view, ['urls' => $urls]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $urls
     */
    public function response(array $urls): Response
    {
        return response($this->view($urls)->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
