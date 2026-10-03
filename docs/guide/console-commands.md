## Console commands

The package ships two Artisan commands for operating the sitemap cache.

### `basekit-seo:sitemap:warm`

Rebuilds the sitemap cache ahead of time so the first crawler never pays for it:

```bash
php artisan basekit-seo:sitemap:warm
# Warmed 3 sitemap document(s) containing 12480 URL(s).
```

Useful as a scheduled task right after a deploy, or after content imports:

```php
// routes/console.php
Schedule::command('basekit-seo:sitemap:warm')->dailyAt('03:00');
```

- **No-op when already warm.** If the catalog is still cached the command
  reports that there is nothing to do and exits successfully, so it is safe to
  schedule unconditionally.
- **`--force`** rebuilds even when the cache is warm:

  ```bash
  php artisan basekit-seo:sitemap:warm --force
  ```

- **Refuses to run when caching is disabled** (`sitemap.cache.enabled` is
  `false`), reporting that there is nothing to cache.
- **Fails cleanly on a broken provider.** A provider that throws is reported as
  an error with a non-zero exit code rather than as an uncaught exception.

Rebuilding takes the same short-lived lock as an on-demand rebuild, so running
this concurrently with crawler traffic is safe.

### `basekit-seo:sitemap:clear`

Drops every cached sitemap document and the catalog:

```bash
php artisan basekit-seo:sitemap:clear
# Cleared 3 cached sitemap document(s).
```

This is the command to reach for after a deploy that changes providers, or from
a model's `saved`/`deleted` observer:

```php
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;

Product::observe(function (): void {
    app(SitemapCache::class)->clear();
});
```

The first crawler after a clear regenerates the sitemap, so pair `clear` with
`warm` if you would rather pay the cost at deploy time than on demand.

### Invalidating from your own code

Both commands delegate to `SitemapCache`, so calling the service directly is
equivalent and easier to reach from application code:

```php
app(SitemapCache::class)->clear();          // drop the cache
app(SitemapGenerator::class)->regenerateNow();  // locked rebuild
```

`clear()` forgets the catalog and every document key derived from it; there is no
separate key registry to keep in sync.