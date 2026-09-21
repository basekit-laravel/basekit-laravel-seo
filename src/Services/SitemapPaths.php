<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use InvalidArgumentException;

/**
 * Derives the package's sitemap route paths from configuration.
 *
 * The base path (`sitemap.path`, default `/sitemap.xml`) is the stable entry
 * point. Chunk documents are served at the same path with `-{n}` inserted
 * before the extension (or appended), so `/sitemap.xml` splits into
 * `/sitemap-1.xml`, `/sitemap-2.xml`, ... — the robots.txt declaration and the
 * sitemap index reference the same paths, keeping everything consistent.
 */
final class SitemapPaths
{
    public function __construct(private readonly string $base)
    {
        if (trim($base) === '') {
            throw new InvalidArgumentException('basekit-laravel-seo.sitemap.path must not be empty.');
        }
    }

    public static function fromConfig(): self
    {
        return new self((string) config('basekit-laravel-seo.sitemap.path', Sitemap::ROUTE_PATH));
    }

    /**
     * The route the primary sitemap (single urlset or sitemap index) is served at.
     */
    public function base(): string
    {
        return $this->base;
    }

    /**
     * The path a specific chunk document is served at.
     */
    public function chunk(int $index): string
    {
        return self::withNumber($this->base, $index);
    }

    /**
     * The route pattern for chunk documents, e.g. `/sitemap-{n}.xml`.
     */
    public function chunkPattern(): string
    {
        return self::withNumber($this->base, '{n}');
    }

    private static function withNumber(string $base, string|int $number): string
    {
        $normalized = rtrim($base, '/');

        if (str_ends_with($normalized, '.xml')) {
            return substr($normalized, 0, -4).'-'.$number.'.xml';
        }

        return $normalized.'-'.$number.'.xml';
    }
}
