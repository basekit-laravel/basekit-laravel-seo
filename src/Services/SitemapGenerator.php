<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use Illuminate\Contracts\Container\Container;
use LogicException;
use RuntimeException;

/**
 * Orchestrates the sitemap pipeline: aggregate the tagged providers, split the
 * result into documents and cache both the catalog and the rendered chunks.
 *
 * Providers are only executed on a cache miss. On a hit the catalog (or a
 * chunk document) is served straight from the cache, so the request flow is
 * always lookup first, generate only when necessary. Generation renders every
 * chunk exactly once — each entry lives in a single in-flight slice — then
 * stores the catalog, keeping the cached set consistent.
 *
 * Generation happens behind a cache lock, so the burst of requests a crawler
 * makes right after the cache expires produces one regeneration rather than
 * one per request. The result is memoised for the remainder of the request so a
 * single request never runs the providers more than once, even when caching is
 * disabled.
 *
 * The instance is bound `scoped`, so the memo is discarded between requests and
 * cannot leak across them under long-running workers.
 */
final class SitemapGenerator
{
    private ?SitemapCatalog $memo = null;

    /** @var array<int, string> Rendered documents held in memory for this request. */
    private array $rendered = [];

    private ?object $memoRequest = null;

    public function __construct(
        private Container $container,
        private SitemapAggregator $aggregator,
        private SitemapChunker $chunker,
        private SitemapRenderer $renderer,
        private SitemapCache $cache,
        private CanonicalUrlResolver $resolver,
    ) {}

    /**
     * The freshness window, in seconds, advertised on sitemap responses.
     *
     * Mirrors the cache TTL so a client cannot hold a stale sitemap for longer
     * than the package itself does.
     */
    public static function maxAge(): int
    {
        if (! (bool) config('basekit-laravel-seo.sitemap.cache.enabled', true)) {
            return 0;
        }

        return max(0, (int) config('basekit-laravel-seo.sitemap.cache.ttl', 3600));
    }

    /**
     * The split catalog, from cache or freshly generated.
     *
     * The memo only ever holds a catalog this request generated itself; a
     * catalog read from the cache is cheap to re-read, and deliberately not
     * memoized so an explicit `SitemapCache::clear()` takes effect immediately
     * for the rest of the request.
     */
    public function catalog(): SitemapCatalog
    {
        $this->syncMemo();

        return $this->memo ?? $this->resolveCatalog();
    }

    /**
     * The XML served at the base sitemap route: a plain `<urlset>` for a
     * single-document site, a `<sitemapindex>` once the site is split.
     *
     * The index-vs-urlset decision is made during a single generation pass so
     * providers never run twice for one request.
     */
    public function baseDocument(): string
    {
        $catalog = $this->catalog();

        if ($catalog->isIndex()) {
            return $this->renderer->index($catalog, $this->requireOrigin());
        }

        return $this->documentXml(1);
    }

    /**
     * The rendered XML for one chunk document, from cache or freshly generated.
     */
    public function documentXml(int $index): string
    {
        $this->syncMemo();

        $cached = $this->cache->document($index);

        if ($cached !== null) {
            return $cached;
        }

        if (array_key_exists($index, $this->rendered)) {
            return $this->rendered[$index];
        }

        [, $xml] = $this->regenerate($index);

        if ($xml === null) {
            throw new LogicException('The requested sitemap document could not be generated.');
        }

        return $xml;
    }

    /**
     * Regenerate the split catalog from scratch, ignoring any cached copy.
     *
     * Exposed for the cache-warming command, which must always hit the
     * providers even when a warm catalog is already present.
     *
     * @return array{0: SitemapCatalog, 1: string|null}
     */
    public function regenerateNow(?int $wantedIndex = null): array
    {
        $this->memo = null;
        $this->rendered = [];

        return $this->synchronized(fn (): array => $this->generate($wantedIndex));
    }

    /**
     * Discard the memoized catalog whenever the container has moved on to a
     * different request.
     *
     * The generator memoizes the catalog it built so a single request never
     * runs the providers twice. Binding it `scoped` is the intended lifecycle,
     * but serving a stale sitemap after a content change is a serious failure
     * mode, so the memo is additionally keyed on the current request instance.
     * That keeps the package correct regardless of how the instance is reused,
     * including in workers that outlive a single request. With no request bound
     * (console commands, queue workers) the memo is always discarded, which is
     * the safe default.
     */
    private function syncMemo(): void
    {
        $current = $this->container->bound('request')
            ? $this->container->make('request')
            : null;

        if ($current === null || $this->memoRequest !== $current) {
            $this->memo = null;
            $this->rendered = [];
            $this->memoRequest = $current;
        }
    }

    private function resolveCatalog(): SitemapCatalog
    {
        $cached = $this->cache->catalog();

        if ($cached instanceof SitemapCatalog) {
            return $cached;
        }

        return $this->regenerate()[0];
    }

    /**
     * Regenerate behind the cache lock, reusing another worker's output if it
     * finished while this one was waiting.
     *
     * Without the double-check a burst of concurrent requests would be
     * serialised by the lock but each one would still regenerate in turn, which
     * defeats the purpose. Re-reading the cache after the lock is acquired means
     * only the first waiter does the work.
     *
     * @return array{0: SitemapCatalog, 1: string|null}
     */
    private function regenerate(?int $wantedIndex = null): array
    {
        return $this->synchronized(function () use ($wantedIndex): array {
            $catalog = $this->cache->catalog();

            if ($wantedIndex === null) {
                return $catalog instanceof SitemapCatalog
                    ? [$catalog, null]
                    : $this->generate($wantedIndex);
            }

            $cached = $this->cache->document($wantedIndex);

            if ($cached !== null && $catalog instanceof SitemapCatalog) {
                return [$catalog, $cached];
            }

            return $this->generate($wantedIndex);
        });
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function synchronized(callable $callback): mixed
    {
        return $this->cache->synchronized($callback);
    }

    /**
     * Stream every provider entry through the chunker, rendering and caching
     * each document as its slice is finalized.
     *
     * @return array{0: SitemapCatalog, 1: string|null}
     */
    private function generate(?int $wantedIndex = null): array
    {
        $wanted = null;
        $cacheEnabled = $this->cache->isEnabled();

        $catalog = $this->chunker->chunk(
            $this->aggregator->stream(),
            function (int $index, array $entries) use ($wantedIndex, &$wanted, $cacheEnabled): void {
                $xml = $this->renderer->urlset($entries);

                if ($wantedIndex === $index) {
                    $wanted = $xml;
                }

                $this->cache->putDocument($index, $xml);

                // With caching on, the cache serves the rest of this request.
                // With caching off there is nothing to read back from, so keep
                // the documents in memory to avoid regenerating per request.
                if (! $cacheEnabled) {
                    $this->rendered[$index] = $xml;
                }
            },
        );

        $this->cache->putCatalog($catalog);
        $this->memo ??= $catalog;

        return [$catalog, $wanted];
    }

    private function requireOrigin(): string
    {
        $origin = $this->resolver->resolve();

        if (! $origin instanceof CanonicalUrl) {
            throw new RuntimeException(
                'A sitemap index requires a trusted canonical origin. Configure basekit-laravel-seo.canonical.base_url or app.url, or allow the request host in basekit-laravel-seo.canonical.trusted_hosts.',
            );
        }

        return $origin->toString();
    }
}
