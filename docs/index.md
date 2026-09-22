# Basekit Laravel SEO

SEO handled in **one place**: page metadata, structured data (JSON-LD),
robots.txt and XML sitemaps for Basekit-powered Laravel applications.

Install it, drop one component into your layout, and describe each page from
your controller:

```php
seo()
    ->title('Services')
    ->description('What we do.')
    ->canonicalUrl('https://acme.test/services');
```

That is rendered in your `<head>`:

```blade
<x-basekit-laravel-seo::head />
```

## What you get

- **Page metadata** — title, description, canonical URL, robots directives,
  Open Graph, Twitter cards and `hreflang` alternates.
- **Structured data** — JSON-LD builders for `WebSite`, `WebPage`, `Article`
  and `Organization`.
- **robots.txt** — served automatically from configuration.
- **XML sitemap** — aggregates entries from your own providers and serves
  `/sitemap.xml` (splitting into multiple documents automatically for large
  sites).

Nothing is guessed: the package only emits values you (or a resolver) provide.

## Requirements

PHP `^8.4|^8.5` and Laravel `^13`.

## What this package does not do

- It does not know your content. You connect your pages, posts and products
  through [resolvers](./guide/resolvers) and
  [sitemap providers](./guide/sitemap).
- It does not store anything — no database, no admin UI. Sitemap output is
  cached in the Laravel cache, nothing else is persisted.

Continue to the [Getting started guide](./guide/getting-started).