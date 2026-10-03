<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\CountingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\FailingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\SeriesSitemapProvider;
use Illuminate\Contracts\Cache\Repository;

function console_bind_provider(SitemapProvider $provider): void
{
    app()->instance($provider::class, $provider);
    app()->tag($provider::class, SitemapProvider::PROVIDER_TAG);
}

function console_cache_store(): Repository
{
    return app('cache')->store('array');
}

it('clears every cached document', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);

    console_bind_provider(new SeriesSitemapProvider(3));

    $this->artisan('basekit-seo:sitemap:clear')->assertSuccessful();

    console_bind_provider(new SeriesSitemapProvider(3));

    $this->get('/sitemap.xml')->assertOk();
    $this->get('/sitemap-1.xml')->assertOk();
    $this->get('/sitemap-2.xml')->assertOk();
    $this->get('/sitemap-3.xml')->assertOk();

    expect(console_cache_store()->get(SitemapCache::KEY_PREFIX.':index'))->not->toBeNull()
        ->and(console_cache_store()->get(SitemapCache::KEY_PREFIX.':1'))->toBeString()
        ->and(console_cache_store()->get(SitemapCache::KEY_PREFIX.':3'))->toBeString();

    $this->artisan('basekit-seo:sitemap:clear')->assertSuccessful();

    expect(console_cache_store()->get(SitemapCache::KEY_PREFIX.':index'))->toBeNull()
        ->and(console_cache_store()->get(SitemapCache::KEY_PREFIX.':1'))->toBeNull()
        ->and(console_cache_store()->get(SitemapCache::KEY_PREFIX.':3'))->toBeNull();
});

it('warms the cache so crawler requests never run the providers', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    CountingSitemapProvider::reset();
    console_bind_provider(new SeriesSitemapProvider(5));
    console_bind_provider(new CountingSitemapProvider);

    $this->artisan('basekit-seo:sitemap:warm')
        ->expectsOutputToContain('Warmed 3 sitemap document(s) containing 6 URL(s).')
        ->assertSuccessful();

    expect(CountingSitemapProvider::$executions)->toBe(1);

    $this->get('/sitemap.xml')->assertOk();
    $this->get('/sitemap-1.xml')->assertOk();
    $this->get('/sitemap-3.xml')->assertOk();

    expect(CountingSitemapProvider::$executions)->toBe(1);
});

it('is a no-op when the catalog is already warm, and regenerates with --force', function (): void {
    CountingSitemapProvider::reset();
    console_bind_provider(new CountingSitemapProvider);

    $this->artisan('basekit-seo:sitemap:warm')
        ->expectsOutputToContain('Warmed')
        ->assertSuccessful();

    // Second run must report a no-op rather than claiming it warmed anything.
    $this->artisan('basekit-seo:sitemap:warm')
        ->expectsOutputToContain('already warm')
        ->assertSuccessful();

    expect(CountingSitemapProvider::$executions)->toBe(1);

    $this->artisan('basekit-seo:sitemap:warm --force')->assertSuccessful();

    expect(CountingSitemapProvider::$executions)->toBe(2);
});

it('refuses to warm when caching is disabled', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.enabled', false);

    $this->artisan('basekit-seo:sitemap:warm')->assertFailed();
});

it('reports a provider failure instead of throwing', function (): void {
    console_bind_provider(new FailingSitemapProvider);

    $this->artisan('basekit-seo:sitemap:warm')
        ->expectsOutputToContain('Sitemap warm-up failed')
        ->assertFailed();
});
