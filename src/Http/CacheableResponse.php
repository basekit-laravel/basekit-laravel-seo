<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Builds HTTP responses for the package's public, crawler-facing endpoints.
 *
 * Sitemaps and robots.txt change rarely but are fetched constantly — crawlers,
 * monitors and preview bots all poll them, and a large sitemap can be tens of
 * megabytes. Without caching headers Symfony answers `Cache-Control: no-cache,
 * private`, which explicitly forbids every intermediary from reusing the
 * response, so the full body is re-transferred on every single request.
 *
 * Each response therefore carries a strong ETag derived from the body and a
 * `public, max-age` freshness window, letting both browsers and CDNs revalidate
 * cheaply and answer conditional requests with a bodiless 304.
 */
final class CacheableResponse
{
    /**
     * Build a cacheable response, downgrading to 304 when the client already
     * holds the current body.
     *
     * @param  int  $maxAge  Freshness window in seconds. `0` means "revalidate on
     *                       every use": the ETag is still emitted and a
     *                       conditional request still gets a bodiless 304, so
     *                       the client never re-downloads an unchanged body.
     */
    public static function make(Request $request, string $body, string $contentType, int $maxAge): Response
    {
        $etag = '"'.sha1($body).'"';

        $response = response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age='.max(0, $maxAge),
            'ETag' => $etag,
            // Sitemap payloads are never HTML; stop legacy clients from
            // sniffing a mis-cached or corrupted body into a script context.
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Honour the validator regardless of max-age. A `max-age=0` response is
        // stale the moment it is stored, so the client is required to
        // revalidate — answering 304 is what keeps it from re-transferring the
        // body, which is the entire point of emitting an ETag at all.
        if ($response->isNotModified($request)) {
            $response->setNotModified();
        }

        return $response;
    }
}
