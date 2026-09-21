<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Renders an XML sitemap (urlset) from a list of URL descriptors.
 *
 * Each URL is an array with a `loc` plus optional `lastmod`, `changefreq` and
 * `priority`. Values are escaped by the sitemap view and disallowed XML control
 * characters are stripped before rendering, so malicious or malformed input
 * cannot corrupt the generated document.
 */
class Sitemap
{
    /**
     * The route this package serves the sitemap at. The robots.txt sitemap
     * declaration references the same path so the two always stay in sync.
     */
    public const ROUTE_PATH = '/sitemap.xml';

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
        return view('basekit-laravel-seo::'.$this->view, [
            'urls' => array_map($this->normalizeEntry(...), $urls),
        ]);
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

    /**
     * Validate an entry and strip disallowed XML control characters.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function normalizeEntry(array $entry): array
    {
        $loc = $entry['loc'] ?? null;

        if (! is_scalar($loc) || (string) $loc === '') {
            throw new InvalidArgumentException('Every sitemap entry must include a non-empty loc.');
        }

        return array_map(
            static fn (mixed $value): mixed => is_string($value)
                ? (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value)
                : $value,
            $entry,
        );
    }
}
