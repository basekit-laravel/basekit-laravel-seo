<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

/**
 * Caches aggregated and split sitemap output in the Laravel cache.
 *
 * Cache keys are deterministic and versioned so a future package release can
 * bump the namespace instead of shipping incompatible entries. The aggregated
 * catalog lives under the `:index` key and each rendered chunk under its own
 * `:1..N` key. Document keys are fully derivable from that namespace, so
 * `clear()` can remove every package-owned key without a separate registry.
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

    private const string LOCK_KEY = self::KEY_PREFIX.':regenerate-lock';

    /**
     * How many chunk keys `clear()` sweeps when no catalog has been cached, so
     * orphans left behind by an aborted generation are still removed without
     * the sweep becoming unbounded.
     */
    private const int CLEAR_FALLBACK_CHUNKS = 64;

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
    }

    /**
     * Run `$callback` while holding a regeneration lock.
     *
     * Sitemaps are fetched by crawlers in bursts: the base document and then
     * every chunk, back to back. When the cache expires all of those requests
     * miss simultaneously, and without coordination each one would regenerate
     * the whole sitemap. Serialising them behind a lock means a single
     * regeneration serves the entire burst.
     *
     * Stores without lock support (and the `null`/`array` drivers used in
     * tests) run the callback directly rather than failing.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function synchronized(callable $callback, int $seconds = 10): mixed
    {
        $provider = $this->store()->getStore();

        if (! $provider instanceof LockProvider) {
            return $callback();
        }

        return $provider->lock(self::LOCK_KEY, $seconds)->get($callback);
    }

    /**
     * Whether sitemap caching is switched on.
     *
     * Exposed so callers can skip their own memoization when the cache already
     * holds the rendered documents.
     */
    public function isEnabled(): bool
    {
        return $this->enabled();
    }

    /**
     * Remove every sitemap cache entry so the next request regenerates the
     * aggregation, the split and all rendered documents.
     *
     * This is the intended invalidation API for consumers — domain packages may
     * call `app(SitemapCache::class)->clear()` after content changes without
     * knowing any cache internals. Chunk keys are a contiguous `:1..N` range
     * underneath the package's own key prefix, so they are swept directly; a
     * cached catalog tells us how many to sweep, and the fallback sweep keeps
     * orphaned keys from an aborted generation from surviving when no catalog
     * was ever written.
     */
    public function clear(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $store = $this->store();

        // Read the catalog *before* dropping the index, otherwise the chunk
        // count is always zero and a large sitemap would keep stale documents.
        $catalog = $this->catalog();
        $chunks = $catalog instanceof SitemapCatalog ? $catalog->count() : 0;

        $store->forget(self::KEY_INDEX);

        for ($index = 1; $index <= max($chunks, self::CLEAR_FALLBACK_CHUNKS); $index++) {
            $store->forget($this->documentKey($index));
        }
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
}
