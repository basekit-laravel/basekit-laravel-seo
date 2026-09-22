## XML sitemap

The package serves a sitemap at `/sitemap.xml` (path configurable). Its URLs
come from **providers** — small classes you write, one per content domain.

### A sitemap entry

`SitemapEntry` describes one URL:

```php
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

new SitemapEntry(
    loc: 'https://acme.test/about',
    lastmod: '2026-09-21T10:30:00+00:00', // optional; ISO-8601
    changefreq: 'monthly',                // optional; see table below
    priority: 0.8,                        // optional; 0.0 – 1.0
);
```

Supported `changefreq` values: `always`, `hourly`, `daily`, `weekly`,
`monthly`, `yearly`, `never`.

### Writing a provider

Return an iterable of `SitemapEntry` objects:

```php
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

final class ProductSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        foreach (Product::where('visible', true)->latest()->get() as $product) {
            yield new SitemapEntry(
                loc: route('products.show', $product),
                lastmod: $product->updated_at?->toIso8601String(),
                changefreq: 'weekly',
            );
        }
    }
}
```

### Registering providers

Tag each provider in a service provider; registration order is the order URLs
appear in the sitemap:

```php
use App\Seo\ProductSitemapProvider;
use App\Seo\PageSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;

public function register(): void
{
    $this->app->tag(
        [ProductSitemapProvider::class, PageSitemapProvider::class],
        SitemapProvider::PROVIDER_TAG,
    );
}
```

### What the package does with the entries

- **Splits large sites automatically.** `SitemapEntry` output is split at entry
  boundaries when it exceeds `sitemap.max_urls` (50,000) or
  `sitemap.max_bytes` (50 MiB). `/sitemap.xml` then becomes a sitemap index and
  the documents are served at `/sitemap-1.xml`, `/sitemap-2.xml`, ...
- **Drops duplicates** by URL (first occurrence wins).
- **Fails loudly.** A provider that throws or yields something that is not a
  `SitemapEntry` surfaces its error — no partial sitemap is served. A single
  entry too large to fit in one document raises `InvalidArgumentException`.
- **Missing chunks are 404.** A request for a chunk that does not exist (for
  example `/sitemap-99.xml` when only two documents were generated) returns
  `404` instead of an empty document.

### Caching

Aggregation and rendering are cached so providers do **not** run on every
request:

| Config | Default | Meaning |
| --- | --- | --- |
| `sitemap.cache.enabled` | `true` | Master switch. |
| `sitemap.cache.ttl` | `3600` | Seconds the catalog and documents are kept. |
| `sitemap.cache.store` | `null` | Cache store; `null` = default store. |

Invalidate after content changes — this is the intended API, no cache internals
needed:

```php
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;

app(SitemapCache::class)->clear();
```