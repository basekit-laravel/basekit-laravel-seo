# XML sitemap

The package serves a sitemap at:

```text
GET /sitemap.xml
```

The endpoint aggregates URLs contributed by **providers** — small classes that
know about one application domain (pages, products, blog posts, ...) — splits
the result into standard-sized documents, and renders them as XML. The SEO
package stays completely unaware of what a "page" or "product" is; it only
speaks in `SitemapEntry` value objects.

## SitemapEntry

`BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry` is an immutable,
`readonly` value object describing one sitemap URL:

```php
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

new SitemapEntry(
    loc: 'https://acme.test/about',
    lastmod: '2026-09-21T10:30:00+00:00',
    changefreq: 'monthly',
    priority: 0.8,
);
```

| Property | Type | Rules |
| --- | --- | --- |
| `loc` | `string` | Absolute `http`/`https` URL, no credentials or fragments, no control characters; validated and normalized through the package's canonical URL rules. |
| `lastmod` | `string\|DateTimeInterface\|null` | Any parseable date; normalized to ISO-8601 with offset (`2026-09-21T10:30:00+00:00`). |
| `changefreq` | `string\|null` | One of `always, hourly, daily, weekly, monthly, yearly, never` (case-insensitive). |
| `priority` | `float\|int\|string\|null` | Between `0.0` and `1.0`; out-of-range values throw. |

Everything is validated at construction, so a provider can never hand the
renderer a corrupt `loc`, an arbitrary `changefreq`, or a poisoned `lastmod`.
`toArray()` and `fromArray()` (de)serialize entries for rendering/round-tripping;
optional fields are omitted when absent.

## The provider contract

`BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider` is intentionally
tiny:

```php
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

final class ProductSitemapProvider implements SitemapProvider
{
    public function entries(): iterable
    {
        yield new SitemapEntry(loc: 'https://acme.test/products/foo');
        yield new SitemapEntry(loc: 'https://acme.test/products/bar');
    }
}
```

`entries()` may return any iterable — arrays, generators, collections. The
provider decides internally where its URLs come from (Eloquent, a content API,
a static list); the package does not care.

## Registering providers

Providers are discovered through Laravel's container tagging. Tag them in any
service provider:

```php
use App\Seo\ProductSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;

public function register(): void
{
    $this->app->tag(ProductSitemapProvider::class, SitemapProvider::PROVIDER_TAG);
}
```

Multiple providers can be tagged in one call. Registration order is the
rendering order.

## Aggregation

The package's single `SitemapAggregator` service resolves the tagged providers
and runs them:

```php
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapAggregator;

$entries = app(SitemapAggregator::class)->entries(); // list<SitemapEntry>
```

Behavior:

- **Ordering** is deterministic: providers run in container registration order
  and their entries keep their own order. There is no alphabetical sorting and
  no provider-priority API.
- **Duplicates** are dropped by canonical `loc`: the first occurrence wins,
  later ones are skipped, so the document never contains accidental duplicate
  `<url>` elements.
- **Failures are loud.** A provider that throws, or that yields something that
  is not a `SitemapEntry`, propagates its error to the caller. A partial
  sitemap is worse than an explicit failure, so nothing is swallowed.

Deduplication happens before splitting, so duplicates never create extra
documents.

## Splitting and the sitemap index

Aggregate output is split at **entry boundaries** — never at an arbitrary byte
position. A document is finalized when adding the next entry would exceed
either of the standard-sitemap limits:

| Config | Default | Meaning |
| --- | --- | --- |
| `basekit-laravel-seo.sitemap.max_urls` | `50_000` | Maximum URLs per document. |
| `basekit-laravel-seo.sitemap.max_bytes` | `50_000_000` | Maximum serialized document size in bytes (UTF-8 byte length, including the XML declaration and wrapper). |

Byte accounting uses the same exact serialized sizes the renderer emits, so a
served document is **never** larger than `max_bytes`. Splitting keys off byte
size, not character count, which matters for non-ASCII URLs. An individual
entry that cannot fit in a document even on its own raises
`InvalidArgumentException` (a possibly oversized provider entry) and the route
responds with an error — rather than producing an unusable chunk.

### Single-document sites behave exactly as before

As long as the aggregate fits in one document, `/sitemap.xml` is a plain
`<urlset>`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://acme.test/about</loc>
        <lastmod>2026-09-21T10:30:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
```

A site with no providers still serves that empty-but-well-formed `<urlset>`, so
search engines and existing tests keep working unchanged.

### Split sites serve an index plus numbered documents

Once the aggregate needs more than one document, `/sitemap.xml` becomes a
`<sitemapindex>` and each document is served at a deterministic route derived
from the configured path (`<path minus .xml>-{n}.xml`):

```text
GET /sitemap.xml       -> <sitemapindex> pointing at the documents below
GET /sitemap-1.xml     -> <urlset> with the first N entries
GET /sitemap-2.xml     -> <urlset> with the next N entries
```

Requests for a chunk number beyond the split respond `404`. Example index:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://acme.test/sitemap-1.xml</loc>
    </sitemap>
    <sitemap>
        <loc>https://acme.test/sitemap-2.xml</loc>
    </sitemap>
</sitemapindex>
```

Index `<loc>` URLs are built from the **trusted canonical origin** (the same
resolution used for canonical URLs: `canonical.base_url`, then `app.url`, then
an allow-listed request host) plus the chunk paths — never from a raw `Host`
header. When the site is split but no safe origin is available, the route
rejects the request (an index whose URLs might be poisoned is worse than none).
Entries carry no `lastmod`; the index describes documents, not contents.

## Caching

Aggregated and split output is stored in the Laravel cache so providers are
**not re-executed on every request**. Control it under
`basekit-laravel-seo.sitemap.cache`:

| Config | Default | Meaning |
| --- | --- | --- |
| `cache.enabled` | `true` | Master switch (`BASEKIT_SEO_SITEMAP_CACHE` env). |
| `cache.ttl` | `3600` | TTL in seconds for the catalog and every document. |
| `cache.store` | `null` | Cache store name; `null` uses the application's default store. |

Cache keys are deterministic and versioned, and each split document lives
under its own key. The intended public invalidation API — call this after
content changes in a domain package, no cache internals needed:

```php
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;

$cache = app(SitemapCache::class);
$cache->clear(); // removes the catalog and every document
```

When the catalog expires but a chunk request still lands on a cached document,
the missing pieces regenerate automatically. Disabling the cache (`enabled =>
false`) turns the service into a transparent pass-through: every request
regenerates, and the chunk routes still return correct documents because a
single generation pass captures the requested slice.

## Rendering

The aggregated route is rendered by the package's `SitemapRenderer` (an
internal, string-based renderer shared by the single and split layouts). Values
are XML-escaped (the same escaping rules as the package's Blade output) and
invalid XML control characters are stripped, so entries cannot break the
document. Optional entry fields are only emitted when set; empty elements are
never generated.

The legacy `Services\Sitemap` API — rendering an array of URL descriptors
through the published sitemap view — keeps working unchanged. Note that the
automated `/sitemap.xml` route no longer builds on that Blade view; it uses the
built-in renderer so splitting, index and byte accounting are exact. The
published view remains for the legacy API and for you to preview/override page
markup.

## Security

- `loc`, `lastmod`, `changefreq` and `priority` are validated
  (`SitemapEntry`), and every value is XML-escaped at render time.
- Locations can only ever be `http`/`https` URLs — `javascript:`, `data:`,
  `file:`, `ftp:` and malformed URLs are rejected at construction.
- Control characters (NUL, CRLF, ...) are rejected in `loc` and cannot survive
  in `lastmod`/`changefreq`/`priority` because those are normalized values.
- The sitemap contents never come from the HTTP `Host` header; entry URLs are
  authoritative, and index URLs are built from the trusted canonical origin —
  a poisoned host cannot inject URLs into the document or the index.
- Byte-limit enforcement uses actual serialized size (UTF-8), so an entry
  cannot smuggle bytes past `max_bytes`.

## Current limitations

This phase deliberately does **not** implement:

- special domain sitemaps (video, image, news) and Google extensions,
- structured-data-driven or hreflang sitemap entries,
- an admin/UI, Eloquent persistence, or queued/offline generation.

The `SitemapProvider` contract (`entries(): iterable<SitemapEntry>`) stays as
it is, so single/divided rendering is invisible to providers.