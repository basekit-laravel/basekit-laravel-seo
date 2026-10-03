<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;
use Generator;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Discovers the tagged sitemap providers and aggregates their entries.
 *
 * Providers run in container registration order, so output is deterministic.
 * The first occurrence of each canonical loc wins; later duplicates are
 * dropped. Provider failures are never swallowed — a partial sitemap is worse
 * than an explicit failure, so exceptions propagate to the caller.
 *
 * `stream()` is the primary API and yields entries lazily, so a site with a
 * million URLs never holds a million `SitemapEntry` objects at once. Only the
 * set of already-emitted locations is retained, because de-duplication is
 * order-dependent and cannot be resolved without remembering what came first.
 */
final readonly class SitemapAggregator
{
    public function __construct(private Container $container) {}

    /**
     * Every entry, in provider order, with duplicate locations removed.
     *
     * Retained for callers that need a countable array. Prefer `stream()` when
     * rendering, because materialising every entry is what makes large sitemaps
     * expensive.
     *
     * @return list<SitemapEntry>
     */
    public function entries(): array
    {
        return iterator_to_array($this->stream(), false);
    }

    /**
     * Lazily yield every entry, in provider order, with duplicate locations removed.
     *
     * @return Generator<int, SitemapEntry>
     */
    public function stream(): Generator
    {
        $seen = [];

        foreach ($this->container->tagged(SitemapProvider::PROVIDER_TAG) as $provider) {
            if (! $provider instanceof SitemapProvider) {
                throw new InvalidArgumentException(
                    sprintf('Sitemap providers must implement [%s], got [%s].', SitemapProvider::class, get_debug_type($provider)),
                );
            }

            foreach ($provider->entries() as $entry) {
                // Defensive runtime guard: the interface PHPDoc promises SitemapEntry,
                // but a third-party provider may still yield garbage and must fail loudly.
                // @phpstan-ignore-next-line
                if (! $entry instanceof SitemapEntry) {
                    throw new InvalidArgumentException(
                        sprintf('Sitemap providers must yield [%s] instances, got [%s].', SitemapEntry::class, get_debug_type($entry)),
                    );
                }

                if (isset($seen[$entry->loc])) {
                    continue;
                }

                $seen[$entry->loc] = true;

                yield $entry;
            }
        }
    }
}
