<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
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
 * stores the catalog, keeping the cached set consistent. A single request
 * never runs the providers more than once, even when caching is disabled.
 */
final readonly class SitemapGenerator
{
    public function __construct(
        private SitemapAggregator $aggregator,
        private SitemapChunker $chunker,
        private SitemapRenderer $renderer,
        private SitemapCache $cache,
        private CanonicalUrlResolver $resolver,
    ) {}

    /**
     * The split catalog, from cache or freshly generated.
     */
    public function catalog(): SitemapCatalog
    {
        $cached = $this->cache->catalog();

        if ($cached instanceof SitemapCatalog) {
            return $cached;
        }

        return $this->regenerate()[0];
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
        $catalog = $this->cache->catalog();

        if ($catalog instanceof SitemapCatalog && $catalog->isIndex()) {
            return $this->renderer->index($catalog, $this->requireOrigin());
        }

        if ($catalog instanceof SitemapCatalog) {
            $cached = $this->cache->document(1);

            if ($cached !== null) {
                return $cached;
            }
        }

        [$fresh, $xml] = $this->regenerate(1);

        if ($fresh->isIndex()) {
            return $this->renderer->index($fresh, $this->requireOrigin());
        }

        if ($xml === null) {
            throw new LogicException('The requested sitemap document could not be generated.');
        }

        return $xml;
    }

    /**
     * The rendered XML for one chunk document, from cache or freshly generated.
     */
    public function documentXml(int $index): string
    {
        $cached = $this->cache->document($index);

        if ($cached !== null) {
            return $cached;
        }

        [, $xml] = $this->regenerate($index);

        if ($xml === null) {
            throw new LogicException('The requested sitemap document could not be generated.');
        }

        return $xml;
    }

    /**
     * Regenerate the split catalog, caching each rendered document along the way.
     *
     * When a chunk index is supplied, the XML of that exact slice is captured
     * and returned so requests work even while caching is disabled (writes no-op).
     *
     * @return array{0: SitemapCatalog, 1: string|null}
     */
    private function regenerate(?int $wantedIndex = null): array
    {
        $wanted = null;

        $catalog = $this->chunker->chunk(
            $this->aggregator->entries(),
            function (int $index, array $entries) use ($wantedIndex, &$wanted): void {
                $xml = $this->renderer->urlset($entries);

                if ($wantedIndex === $index) {
                    $wanted = $xml;
                }

                $this->cache->putDocument($index, $xml);
            },
        );

        $this->cache->putCatalog($catalog);

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
