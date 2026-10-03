<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use InvalidArgumentException;

/**
 * Validates the package's own configuration.
 *
 * Every value the package cannot render a sane result with is rejected here, at
 * boot, instead of surfacing as a 500 on the first crawler request. That turns
 * "the sitemap 404s in production" into "the deploy fails", which is much
 * cheaper to diagnose.
 *
 * Only values the package fully controls are checked. Origin/host settings are
 * intentionally left alone: they legitimately depend on the request (and on
 * `app.url`, which is often set at runtime), so the package fails with a clear
 * message at the point of use instead.
 */
final class ConfigValidator
{
    /**
     * @throws InvalidArgumentException when a value cannot produce valid output.
     */
    public static function validate(): void
    {
        self::sitemapMaxUrls();
        self::sitemapMaxBytes();
        self::cacheTtl('basekit-laravel-seo.sitemap.cache.ttl', (int) config('basekit-laravel-seo.sitemap.cache.ttl', 3600));
        self::cacheTtl('basekit-laravel-seo.robots.cache_ttl', (int) config('basekit-laravel-seo.robots.cache_ttl', 3600));
    }

    private static function sitemapMaxUrls(): void
    {
        $maxUrls = (int) config('basekit-laravel-seo.sitemap.max_urls', 50_000);

        if ($maxUrls < 1) {
            throw new InvalidArgumentException(sprintf(
                'basekit-laravel-seo.sitemap.max_urls must be at least 1, got %d.',
                $maxUrls,
            ));
        }

        // A single document may contain at most 50,000 <url> entries per the
        // sitemap protocol; anything above it can never be emitted.
        if ($maxUrls > 50_000) {
            throw new InvalidArgumentException(sprintf(
                'basekit-laravel-seo.sitemap.max_urls must not exceed 50000 (the sitemap protocol limit), got %d.',
                $maxUrls,
            ));
        }
    }

    private static function sitemapMaxBytes(): void
    {
        $maxBytes = (int) config('basekit-laravel-seo.sitemap.max_bytes', 50 * 1024 * 1024);
        $scaffold = SitemapRenderer::scaffoldBytes();

        if ($maxBytes < $scaffold) {
            throw new InvalidArgumentException(sprintf(
                'basekit-laravel-seo.sitemap.max_bytes (%d) is too small to hold an empty sitemap document (minimum %d).',
                $maxBytes,
                $scaffold,
            ));
        }

        // Uncompressed sitemap documents are capped at 50 MB by the protocol.
        if ($maxBytes > 50 * 1024 * 1024) {
            throw new InvalidArgumentException(sprintf(
                'basekit-laravel-seo.sitemap.max_bytes must not exceed %d bytes (the sitemap protocol limit), got %d.',
                50 * 1024 * 1024,
                $maxBytes,
            ));
        }
    }

    /**
     * A TTL of 0 is allowed: it means "no caching", which is a legitimate setup
     * and is why it is treated as a pass rather than a failure.
     */
    private static function cacheTtl(string $key, int $ttl): void
    {
        if ($ttl < 0) {
            throw new InvalidArgumentException(sprintf(
                '%s must be 0 (disabled) or a positive number of seconds, got %d.',
                $key,
                $ttl,
            ));
        }
    }
}
