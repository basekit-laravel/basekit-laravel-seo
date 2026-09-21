# XML sitemap

The package serves a sitemap at:

```text
GET /sitemap.xml
```

The endpoint aggregates URLs contributed by **providers** — small classes that
know about one application domain (pages, products, blog posts, ...) — and
renders them through the package's sitemap view. The SEO package stays
completely unaware of what a "page" or "product" is; it only speaks in
`SitemapEntry` value objects.

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

## Rendering

The route serves aggregated entries as a UTF-8 XML document with an
`application/xml` content type:

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

Optional fields are only emitted when set; empty elements are never generated.
All values are XML-escaped (Blade output escaping) and invalid XML control
characters are stripped, so entries cannot break the document. The legacy
`Services\Sitemap` API — rendering an array of URL descriptors through the
published sitemap view — keeps working unchanged and remains the renderer the
aggregated route builds on.

The rendered view can be customized by publishing the package views
(`php artisan vendor:publish --tag="basekit-laravel-seo-views"`) or by pointing
`basekit-laravel-seo.views.sitemap` at your own template.

## Security

- `loc`, `lastmod`, `changefreq` and `priority` are validated
  (`SitemapEntry`), and every value is XML-escaped at render time.
- Locations can only ever be `http`/`https` URLs — `javascript:`, `data:`,
  `file:`, `ftp:` and malformed URLs are rejected at construction.
- Control characters (NUL, CRLF, ...) are rejected in `loc` and cannot survive
  in `lastmod`/`changefreq`/`priority` because those are normalized values.
- The sitemap contents never come from the HTTP `Host` header; entry URLs are
  authoritative, so a poisoned host cannot inject URLs into the document.

## Current limitations

This phase deliberately does **not** implement:

- sitemap indexes (`sitemap_index`) and automatic splitting,
- handling of the 50,000-URL / 50MB limits,
- persistent sitemap caching.

The `SitemapProvider` contract (`entries(): iterable<SitemapEntry>`) is shaped
so index/chunking support can be layered on later without changing providers.
Expect these in a future phase after real-world usage shows the need.