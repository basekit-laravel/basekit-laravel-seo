<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Discovers the tagged sitemap providers and aggregates their entries.
 *
 * Providers run in container registration order, so output is deterministic.
 * The first occurrence of each canonical loc wins; later duplicates are
 * dropped. Provider failures are never swallowed — a partial sitemap is worse
 * than an explicit failure, so exceptions propagate to the caller.
 */
final readonly class SitemapAggregator
{
    public function __construct(private Container $container) {}

    /**
     * @return list<SitemapEntry>
     */
    public function entries(): array
    {
        $entries = [];
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
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}
