<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http\Controllers;

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapGenerator;
use Illuminate\Http\Response;

/**
 * Serves the sitemap entry point.
 *
 * A catalog with a single document is served as a plain `<urlset>` (so small
 * sites behave exactly as before); a split catalog becomes a `<sitemapindex>`.
 * All decisions, rendering and origin resolution happen inside the generator
 * in a single pass, so providers never run twice for one request.
 */
final class SitemapController
{
    public function __invoke(SitemapGenerator $generator): Response
    {
        return response($generator->baseDocument(), 200, ['Content-Type' => 'application/xml']);
    }
}
