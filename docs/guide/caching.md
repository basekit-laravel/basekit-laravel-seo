## HTTP caching

The sitemap and robots endpoints are designed to be scraped by search engines on
a schedule, so both send normal HTTP caching headers. That lets you put a CDN or
a reverse proxy in front of them and keep crawler traffic off your application
entirely.

### What is sent

| Endpoint | `Cache-Control` | `ETag` | `Vary` |
| --- | --- | --- | --- |
| `/sitemap.xml` | `public, max-age=<sitemap.cache.ttl>` | yes | `Accept-Encoding` |
| `/sitemap-{n}.xml` | `public, max-age=<sitemap.cache.ttl>` | yes | `Accept-Encoding` |
| `/robots.txt` | `public, max-age=<robots.cache_ttl>` | yes | `Accept-Encoding` |

All three also send `X-Content-Type-Options: nosniff`.

### Conditional requests

Every response carries a strong `ETag` derived from the rendered body. When a
crawler revalidates with `If-None-Match` and nothing changed, the package
answers `304 Not Modified` with an empty body and no query is run:

```
GET /sitemap.xml
If-None-Match: "d8f1a0..."

HTTP/1.1 304 Not Modified
ETag: "d8f1a0..."
Cache-Control: public, max-age=3600
```

A `304` is sent whenever the client revalidates with a validator that still
matches, including when `max-age` is `0`. `max-age=0` means "stale immediately,
revalidate before reuse", so honouring the validator is exactly what stops the
client re-downloading an unchanged body.

### Choosing a TTL

`sitemap.cache.ttl` and `robots.cache_ttl` control the HTTP freshness **and**
the server-side cache, so one number keeps both layers consistent. A sensible
starting point is one hour.

Set either to `0` to disable caching entirely. `robots.txt` changes rarely, so
a day (`86400`) is usually better for it than an hour.

### Cache keys

The server-side document cache is keyed on the rendered content, not on the
request, so all visitors and all CDN nodes share one entry. Clearing it does not
require walking a key registry:

```php
app(SitemapCache::class)->clear();
```

### Stampede protection

When the cache is cold, concurrent requests would each run every provider. The
generator takes a short-lived lock around the rebuild and the losers serve the
freshly written document instead of doing the work again:

```php
app(SitemapGenerator::class)->regenerateNow();
```

`regenerateNow()` performs the same locked rebuild on demand, which is what the
`basekit-seo:sitemap:warm` command calls. See
[Console commands](/guide/console-commands).

::: tip Locks are optional
The lock is only taken when the configured cache store implements Laravel's
`LockProvider` contract (Redis, Memcached, DynamoDB and the database store do).
Array, file and `null` stores have no shared lock, so they fall through to a
direct rebuild.
:::