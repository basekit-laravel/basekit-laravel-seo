<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\CountingSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\LockingArrayStore;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\MutableSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\SeriesSitemapProvider;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

function bind_provider(SitemapProvider $provider): void
{
    app()->instance($provider::class, $provider);
    app()->tag($provider::class, SitemapProvider::PROVIDER_TAG);
}

function cache_store(): Repository
{
    return app('cache')->store('array');
}

it('serves the second request from cache without re-running the provider', function (): void {
    CountingSitemapProvider::reset();

    bind_provider(new CountingSitemapProvider);

    $this->get('/sitemap.xml')->assertStatus(200);
    expect(CountingSitemapProvider::$executions)->toBe(1);

    $this->get('/sitemap.xml')->assertStatus(200);
    expect(CountingSitemapProvider::$executions)->toBe(1);
});

it('clearing the cache causes the provider to run again', function (): void {
    CountingSitemapProvider::reset();

    bind_provider(new CountingSitemapProvider);

    $this->get('/sitemap.xml')->assertStatus(200);
    expect(CountingSitemapProvider::$executions)->toBe(1);

    app(SitemapCache::class)->clear();

    $this->get('/sitemap.xml')->assertStatus(200);
    expect(CountingSitemapProvider::$executions)->toBe(2);
});

it('regenerates on every request when caching is disabled', function (): void {
    CountingSitemapProvider::reset();

    config()->set('basekit-laravel-seo.sitemap.cache.enabled', false);

    bind_provider(new CountingSitemapProvider);

    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap.xml')->assertStatus(200);

    expect(CountingSitemapProvider::$executions)->toBe(2);
});

it('uses distinct cache keys for the catalog and each split document', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);

    bind_provider(new SeriesSitemapProvider(2));

    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap-1.xml')->assertStatus(200);
    $this->get('/sitemap-2.xml')->assertStatus(200);

    $indexKey = SitemapCache::KEY_PREFIX.':index';
    $firstKey = SitemapCache::KEY_PREFIX.':1';
    $secondKey = SitemapCache::KEY_PREFIX.':2';

    expect(cache_store()->get($indexKey))->not->toBeNull()
        ->and(cache_store()->get($firstKey))->toBeString()
        ->and(cache_store()->get($secondKey))->toBeString()
        ->and(cache_store()->get($firstKey))->not->toBe(cache_store()->get($secondKey));
});

it('clearing removes every package cache key', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);

    bind_provider(new SeriesSitemapProvider(2));

    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap-1.xml')->assertStatus(200);
    $this->get('/sitemap-2.xml')->assertStatus(200);

    app(SitemapCache::class)->clear();

    expect(cache_store()->get(SitemapCache::KEY_PREFIX.':index'))->toBeNull()
        ->and(cache_store()->get(SitemapCache::KEY_PREFIX.':1'))->toBeNull()
        ->and(cache_store()->get(SitemapCache::KEY_PREFIX.':2'))->toBeNull();
});

it('invalidates both the index and the old chunk documents on change', function (): void {
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    MutableSitemapProvider::reset();

    bind_provider(new MutableSitemapProvider);

    $this->get('/sitemap.xml')->assertStatus(200);
    $singleContent = $this->get('/sitemap-1.xml')->getContent();
    expect($singleContent)->toContain('<urlset ')->not->toContain('<sitemapindex ');

    MutableSitemapProvider::$mode = 2;
    app(SitemapCache::class)->clear();

    $indexContent = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

    expect($indexContent)->toContain('<sitemapindex ')
        ->and($this->get('/sitemap-1.xml')->getStatusCode())->toBe(200)
        ->and($this->get('/sitemap-2.xml')->getStatusCode())->toBe(200)
        ->and($this->get('/sitemap-3.xml')->getStatusCode())->toBe(404);
});

it('a cached chunk is served without refreshing the catalog when only the index is needed', function (): void {
    CountingSitemapProvider::reset();

    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);

    bind_provider(new SeriesSitemapProvider(2));

    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap-1.xml')->assertStatus(200);

    expect(cache_store()->get(SitemapCache::KEY_PREFIX.':1'))->toBeString();
});

it('runs the providers once for a chunk request even when caching is disabled', function (): void {
    CountingSitemapProvider::reset();

    config()->set('basekit-laravel-seo.sitemap.cache.enabled', false);
    config()->set('basekit-laravel-seo.sitemap.max_urls', 2);

    bind_provider(new SeriesSitemapProvider(4));
    bind_provider(new CountingSitemapProvider);

    // The chunk controller resolves the catalog to check the document exists and
    // then renders it. Without a request-scoped memo that is two full
    // regenerations, and every provider query runs twice.
    $this->get('/sitemap-2.xml')->assertStatus(200);

    expect(CountingSitemapProvider::$executions)->toBe(1)
        ->and($this->get('/sitemap-2.xml')->assertStatus(200)->getContent())->toContain('<loc>https://example.test/pages/3</loc>');
});

it('does not regenerate when the cache is warm across requests', function (): void {
    CountingSitemapProvider::reset();

    bind_provider(new CountingSitemapProvider);

    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap.xml')->assertStatus(200);
    $this->get('/sitemap.xml')->assertStatus(200);

    expect(CountingSitemapProvider::$executions)->toBe(1);
});

it('runs the callback inline on cache stores without lock support', function (): void {
    // The array driver has no LockProvider, so the generator must not fail and
    // must still produce its result.
    $cache = new SitemapCache(app('cache'));

    expect($cache->synchronized(static fn (): string => 'regenerated'))->toBe('regenerated');
});

it('rejects a negative cache TTL', function (): void {
    config()->set('basekit-laravel-seo.sitemap.cache.ttl', -1);

    $cache = new SitemapCache(app('cache'));

    expect(fn () => $cache->putDocument(1, '<xml/>'))
        ->toThrow(InvalidArgumentException::class);
});

it('clears every document of a sitemap larger than the fallback sweep', function (): void {
    // More than SitemapCache's fallback sweep, so a clear() that ignored the
    // cached catalog would leave documents behind.
    config()->set('basekit-laravel-seo.sitemap.max_urls', 1);
    app()->instance(SeriesSitemapProvider::class, new SeriesSitemapProvider(70));
    app()->tag(SeriesSitemapProvider::class, SitemapProvider::PROVIDER_TAG);

    $this->get('/sitemap.xml')->assertOk();
    $this->get('/sitemap-70.xml')->assertOk();

    expect(cache()->get('basekit-laravel-seo:sitemap:v1:index'))->not->toBeNull();

    app(SitemapCache::class)->clear();

    expect(cache()->get('basekit-laravel-seo:sitemap:v1:index'))->toBeNull()
        ->and(cache()->get('basekit-laravel-seo:sitemap:v1:1'))->toBeNull()
        ->and(cache()->get('basekit-laravel-seo:sitemap:v1:70'))->toBeNull();
});

it('does not regenerate the catalog when another worker filled the cache while waiting for the lock', function (): void {
    CountingSitemapProvider::reset();
    bind_provider(new CountingSitemapProvider);

    // Simulate a second worker that regenerated the sitemap while this request
    // was queued behind the lock: by the time the lock is acquired, the shared
    // store already holds a catalog and its document.
    $shared = null;

    $store = new LockingArrayStore(function () use (&$shared): void {
        $shared->put('basekit-laravel-seo:sitemap:v1:index', [
            'documents' => [['index' => 1, 'count' => 2, 'bytes' => 120]],
        ], 3600);

        $shared->put('basekit-laravel-seo:sitemap:v1:1', '<urlset>written-by-the-other-worker</urlset>', 3600);
    });

    $shared = new CacheRepository($store);

    Cache::extend('locking-array', fn () => $shared);
    config()->set('cache.stores.locking-array', ['driver' => 'locking-array']);
    config()->set('basekit-laravel-seo.sitemap.cache.store', 'locking-array');

    $response = $this->get('/sitemap.xml')->assertOk();

    expect(CountingSitemapProvider::$executions)->toBe(0)
        ->and($response->getContent())->toContain('written-by-the-other-worker');
});
