<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http\Controllers;

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapGenerator;
use Illuminate\Http\Response;

/**
 * Serves an individual split sitemap document at a deterministic chunk path
 * such as `/sitemap-2.xml`. Requests for a chunk that does not exist return
 * 404 — an invalid chunk number never produces an empty document.
 */
final class SitemapChunkController
{
    public function __invoke(SitemapGenerator $generator, string $n): Response
    {
        $index = (int) $n;

        if ($index < 1 || $generator->catalog()->document($index) === null) {
            abort(404);
        }

        return response($generator->documentXml($index), 200, ['Content-Type' => 'application/xml']);
    }
}
