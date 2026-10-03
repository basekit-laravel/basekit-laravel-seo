<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\SeriesSitemapProvider;

/**
 * Parse a Cache-Control header into its directives.
 *
 * Symfony reorders directives when it builds the header bag, so assertions
 * target the directives rather than their serialized order.
 *
 * @return array<string, string|bool>
 */
function cache_control(?string $header): array
{
    $directives = [];

    foreach (array_filter(explode(',', (string) $header)) as $directive) {
        [$name, $value] = array_pad(explode('=', trim($directive), 2), 2, true);

        $directives[$name] = $value;
    }

    return $directives;
}

/**
 * Sitemaps and robots.txt are polled constantly by crawlers and can be tens of
 * megabytes, so every response must be cacheable by browsers, CDNs and crawlers
 * instead of being re-transferred in full on each request.
 */
it('advertises a public freshness window and an ETag on the sitemap', function (): void {
    $response = $this->get('/sitemap.xml')->assertOk();

    expect(cache_control($response->headers->get('cache-control')))->toBe(['max-age' => '3600', 'public' => true])
        ->and($response->headers->get('etag'))->toBe('"'.sha1($response->getContent()).'"')
        ->and($response->headers->get('x-content-type-options'))->toBe('nosniff');
});

it('advertises a public freshness window and an ETag on robots.txt', function (): void {
    $response = $this->get('/robots.txt')->assertOk();

    expect(cache_control($response->headers->get('cache-control')))->toBe(['max-age' => '3600', 'public' => true])
        ->and($response->headers->get('etag'))->toBe('"'.sha1($response->getContent()).'"')
        ->and($response->headers->get('x-content-type-options'))->toBe('nosniff');
});

it('answers a matching If-None-Match with a bodiless 304', function (string $url): void {
    $etag = $this->get($url)->assertOk()->headers->get('etag');

    expect($etag)->not->toBeNull();

    $response = $this->withHeaders(['If-None-Match' => $etag])->get($url);

    expect($response->status())->toBe(304)
        ->and($response->getContent())->toBe('');
})->with(['/sitemap.xml', '/robots.txt']);

it('serves split chunk documents with the same caching headers', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);

    $this->app->instance(SeriesSitemapProvider::class, new SeriesSitemapProvider(2));
    $this->app->tag(SeriesSitemapProvider::class, 'basekit-laravel-seo.sitemap-providers');

    $response = $this->get('/sitemap-2.xml')->assertOk();

    expect(cache_control($response->headers->get('cache-control')))->toBe(['max-age' => '3600', 'public' => true])
        ->and($response->headers->get('etag'))->toBe('"'.sha1($response->getContent()).'"');
});

it('still revalidates to a 304 when caching is disabled', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.enabled', false);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect(cache_control($response->headers->get('cache-control')))->toBe(['max-age' => '0', 'public' => true])
        ->and($response->headers->get('etag'))->toBe('"'.sha1($response->getContent()).'"');

    // max-age=0 makes the response stale immediately, so the client must
    // revalidate. Emitting an ETag is pointless unless the 304 is honoured.
    $revalidated = $this->withHeaders(['If-None-Match' => $response->headers->get('etag')])->get('/sitemap.xml');

    expect($revalidated->status())->toBe(304)
        ->and($revalidated->getContent())->toBe('');
});

it('answers a stale If-None-Match with a fresh 200', function (): void {
    $response = $this->withHeaders(['If-None-Match' => '"not-the-current-body"'])->get('/sitemap.xml');

    expect($response->status())->toBe(200)
        ->and($response->getContent())->toContain('<urlset');
});

it('derives the advertised freshness window from the configured cache TTL', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.ttl', 900);

    expect(cache_control($this->get('/sitemap.xml')->assertOk()->headers->get('cache-control')))
        ->toBe(['max-age' => '900', 'public' => true]);

    config()->set('basekit-laravel-seo.robots.cache_ttl', 120);

    expect(cache_control($this->get('/robots.txt')->assertOk()->headers->get('cache-control')))
        ->toBe(['max-age' => '120', 'public' => true]);
});

it('re-answers with a fresh ETag when the body changes', function (): void {
    config()->set('basekit-laravel-seo.robots.disallow', ['/private']);

    $before = $this->get('/robots.txt')->assertOk()->headers->get('etag');

    config()->set('basekit-laravel-seo.robots.disallow', ['/other']);

    $after = $this->get('/robots.txt')->assertOk();

    expect($after->headers->get('etag'))->not->toBe($before)
        ->and($this->withHeaders(['If-None-Match' => $before])->get('/robots.txt')->status())->toBe(200);
});
