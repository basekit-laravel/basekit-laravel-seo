## Deployment

Notes for putting the package into production: caching, warm-up, long-running
workers and configuration validation.

### Validate configuration at boot

The package validates the values it cannot render a correct result with while
the service provider boots. A bad value throws an `InvalidArgumentException`
immediately — during `php artisan config:cache`, during `package:discover`, or
on the first request — instead of producing a broken sitemap:

| Key | Requirement |
| --- | --- |
| `sitemap.max_urls` | `1` – `50000` (the sitemap protocol limit) |
| `sitemap.max_bytes` | At least the empty-document size, at most 50 MiB |
| `sitemap.cache.ttl` | `0` (disabled) or positive |
| `robots.cache_ttl` | `0` (disabled) or positive |

This turns "the sitemap 404s in production" into "the deploy fails", which is
much cheaper to diagnose.

### Warm the cache after deploying

A cold cache makes the first crawler request regenerate the whole sitemap.
Trigger the warm-up from your deploy script so that cost happens while nobody is
waiting:

```bash
php artisan basekit-seo:sitemap:warm
php artisan optimize:clear   # only if you also want to drop the framework cache
```

See [Console commands](/guide/console-commands) for the full behaviour.

### Behind a CDN

Both sitemap and robots endpoints send `public`, `Cache-Control`, an `ETag` and
`X-Content-Type-Options: nosniff`, and answer `304 Not Modified` to conditional
requests. A CDN or reverse proxy can therefore serve repeat crawls without
touching the application. Tune `sitemap.cache.ttl` and `robots.cache_ttl` to
match how often you publish.

### Laravel Octane

The package is safe to run under Octane. The services that carry per-request
state are deliberately built so a long-lived worker cannot leak it:

- `SeoManager` and `SitemapGenerator` are registered as **scoped** bindings, so
  Laravel gives each request a fresh instance.
- `SitemapGenerator` additionally keys its memo on the current request object, so
  the catalog it built is never reused by a later request.
- `SitemapChunker` and `SitemapRenderer` are **stateless**. All chunking
  bookkeeping lives in locals inside a single call, so an aborted run leaves
  nothing behind and concurrent calls cannot corrupt each other.
- `SitemapAggregator::stream()` yields entries lazily, so a large sitemap is not
  materialised in memory.

Two things to keep in mind:

1. **Rebuild the cache after a deploy.** Octane keeps workers (and their caches)
   alive across a deploy unless you reload them. Run
   `php artisan basekit-seo:sitemap:clear` (or `warm`) as part of your reload
   sequence.
2. **Do not bind the package's services as singletons yourself.** The container
   bindings already have the correct lifetimes; overriding them with
   `singleton()` reintroduces cross-request state.

### Queue workers

The same advice applies to queue workers: nothing per-request is retained, but a
job that mutates content should invalidate the cache explicitly, since the
sitemap is only rebuilt on a miss.

```php
app(SitemapCache::class)->clear();
```