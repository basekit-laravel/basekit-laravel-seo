<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http\Controllers;

use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapAggregator;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;
use Illuminate\Http\Response;

/**
 * Serves the aggregated sitemap. Aggregation and rendering stay separate: the
 * aggregator collects entries from the tagged providers and the Sitemap service
 * renders them through the published view.
 */
final class SitemapController
{
    public function __invoke(SitemapAggregator $aggregator, Sitemap $sitemap): Response
    {
        $entries = array_map(
            static fn (SitemapEntry $entry): array => $entry->toArray(),
            $aggregator->entries(),
        );

        return $sitemap->response($entries);
    }
}
