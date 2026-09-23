<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

/**
 * Caches aggregated and split sitemap output in the Laravel cache.
 *
 * Cache keys are deterministic and versioned so a future package release can
 * bump the namespace instead of shipping incompatible entries. The aggregated
 * catalog lives under the `:index` key and each rendered chunk under its own
 * `:1..N` key; a tiny registry of written keys lets `clear()` remove every
 * package-owned key on any cache driver without relying on tags.
 *
 * Only aggregate generation is cached — normal `<head>` rendering never touches
 * the cache. When caching is disabled the service degrades to a transparent
 * pass-through (every read misses, every write is a no-op) and the sitemap is
 * simply regenerated per request.
 */
final readonly class SitemapCache
{
    public const int CACHE_VERSION = 1;

    public const string KEY_PREFIX = 'basekit-laravel-seo:sitemap:v1';

    private const string KEY_INDEX = self::KEY_PREFIX.':index';

    private const string KEY_REGISTRY = self::KEY_PREFIX.':keys';

    public function __construct(private CacheManager $cache) {}

    /**
     * The cached catalog, or null on a miss or when caching is disabled.
     */
    public function catalog(): ?SitemapCatalog
    {
        if (! $this->enabled()) {
            return null;
        }

        $data = $this->store()->get(self::KEY_INDEX);

        if (! is_array($data)) {
            return null;
        }

        try {
            return SitemapCatalog::fromArray($data);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Cache the catalog that describes the split documents.
     */
    public function putCatalog(SitemapCatalog $catalog): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->put(self::KEY_INDEX, $catalog->toArray(), $this->ttl());

        $this->register(self::KEY_INDEX);
    }

    /**
     * The cached rendered XML for a chunk document, or null on a miss.
     */
    public function document(int $index): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $content = $this->store()->get($this->documentKey($index));

        return is_string($content) ? $content : null;
    }

    /**
     * Cache a chunk document's rendered XML.
     */
    public function putDocument(int $index, string $xml): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->put($this->documentKey($index), $xml, $this->ttl());

        $this->register($this->documentKey($index));
    }

    /**
     * Remove every sitemap cache entry so the next request regenerates the
     * aggregation, the split and all rendered documents.
     *
     * This is the intended invalidation API for consumers — domain packages may
     * call `app(SitemapCache::class)->clear()` after content changes without
     * knowing any cache internals.
     */
    public function clear(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $keys = $this->store()->get(self::KEY_REGISTRY);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->store()->forget((string) $key);
            }
        }

        $this->store()->forget(self::KEY_REGISTRY);
    }

    private function documentKey(int $index): string
    {
        return self::KEY_PREFIX.':'.$index;
    }

    private function enabled(): bool
    {
        return (bool) config('basekit-laravel-seo.sitemap.cache.enabled', true);
    }

    private function ttl(): int
    {
        $ttl = (int) config('basekit-laravel-seo.sitemap.cache.ttl', 3600);

        if ($ttl < 0) {
            throw new InvalidArgumentException('basekit-laravel-seo.sitemap.cache.ttl must not be negative.');
        }

        return $ttl;
    }

    private function store(): Repository
    {
        $name = config('basekit-laravel-seo.sitemap.cache.store');

        return $this->cache->store($name === '' || $name === null ? null : $name);
    }

    private function register(string $key): void
    {
        $keys = $this->store()->get(self::KEY_REGISTRY);
        $list = is_array($keys) ? $keys : [];

        if (! in_array($key, $list, true)) {
            $list[] = $key;
        }

        $this->store()->put(self::KEY_REGISTRY, $list, $this->ttl());
    }
}
