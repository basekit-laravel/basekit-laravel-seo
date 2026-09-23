# Basekit Laravel SEO

Page metadata, structured data (JSON-LD), robots.txt and XML sitemaps for
Laravel sites in the Basekit ecosystem — in one place.

Install it, drop one component into your layout, and describe each page from
your controller:

```php
seo()
    ->title('Services')
    ->description('What we do.')
    ->canonicalUrl('https://acme.test/services');
```

The component renders that in your `<head>`:

```blade
<x-basekit-laravel-seo::head />
```

## What you get

- **Page metadata** — title, description, canonical URL, robots directives,
  Open Graph, Twitter cards and `hreflang` alternates.
- **Structured data** — JSON-LD builders for `WebSite`, `WebPage`, `Article`
  and `Organization`.
- **robots.txt** — served from configuration.
- **XML sitemap** — entries aggregated from your own providers, split into
  multiple documents automatically for large sites.

Only values you (or a resolver) provide are emitted. Descriptions, canonicals
and alternates are never filled in on their own.

## Requirements

PHP `^8.4|^8.5` and Laravel `^13`.

## Non-goals

- It does not know your content. You connect pages, posts and products with
  [resolvers](./guide/resolvers) and
  [sitemap providers](./guide/sitemap).
- It stores nothing — no database, no admin UI. Sitemap output is cached in
  the Laravel cache; everything else lives in the request.

Continue to the [Getting started guide](./guide/getting-started).
