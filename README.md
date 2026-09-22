# Basekit Laravel SEO

A reusable, optional feature package for Basekit-powered Laravel websites:
centralized metadata, structured data (JSON-LD), robots directives and XML
sitemaps.

The package is a Laravel **package**, not an application feature — it ships a
service provider, configuration, views and routes, and is consumed by any
Laravel application that installs it.

| Component | Constraint |
| --- | --- |
| PHP | `^8.4` or `^8.5` |
| Laravel | `^13` |

## Features

- **Centralized metadata** — one immutable `SeoData` value object (title,
  description, canonical URL, robots, Open Graph, Twitter/X, locale,
  `hreflang` alternates and JSON-LD schemas) resolved per request.
- **`seo()` helper** — a fluent manager for per-request metadata:
  `title()`, `description()`, `canonicalUrl()`, `robots()`, `openGraph()`,
  `twitter()`, `locale()`, `alternate()`, `schema()` and `for($subject)`.
- **Layered resolution** — metadata is merged from config defaults, the first
  supporting `SeoResolver`, then explicit fluent overrides; values no layer
  provides are simply omitted (the package never guesses).
- **`<x-basekit-laravel-seo::head />`** — a Blade component rendering the
  resolved metadata: title (with configurable suffix), description, canonical,
  robots meta, Open Graph, Twitter/X, `hreflang` alternates and JSON-LD.
- **SEO resolvers** — implement the `SeoResolver` contract and tag it to teach
  the package about your own content (models, page classes, anything).
- **Structured data** — hardened JSON-LD builders for `WebSite`, `WebPage`,
  `Article` (incl. `BlogPosting`) and `Organization`; plain arrays accepted
  with the same escaping.
- **Canonical URLs** — validated and normalized (http/https only, no
  credentials, fragments or control characters), resolved from
  `canonical.base_url`, then `app.url`, then an allow-listed request host —
  never from the raw `Host` header.
- **robots.txt** — `/robots.txt` served automatically from configuration; the
  `Sitemap:` line is derived from the trusted canonical origin.
- **XML sitemap** — `/sitemap.xml` whose URLs come from tagged
  `SitemapProvider` implementations. Output is split into `/sitemap-{n}.xml`
  documents (with a `<sitemapindex>`) when it exceeds `max_urls` or
  `max_bytes`, and the aggregate plus rendered documents are cached so
  providers only run on a cache miss (`SitemapCache::clear()` invalidates).
- **No invented output** — any value no layer provides is simply omitted; the
  package never guesses descriptions, canonicals or alternatives for you.

## Installation

```bash
composer require basekit-laravel/basekit-laravel-seo
```

The service provider is registered automatically through Composer's Laravel
package discovery. Publish and review the configuration:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-config"
```

## Quick start

Render the head in your layout:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-basekit-laravel-seo::head />
</head>
```

Set metadata from a controller:

```php
use App\Models\Article;

public function show(Article $article)
{
    seo()
        ->for($article)
        ->title($article->title)
        ->description($article->excerpt)
        ->canonicalUrl(route('articles.show', $article));

    return view('articles.show', ['article' => $article]);
}
```

For content types that own their metadata, implement `SeoResolver` and tag it;
for sitemaps, implement `SitemapProvider` and tag it. Once installed, the
package also serves `/sitemap.xml`, `/sitemap-{n}.xml` (when split) and
`/robots.txt` automatically.

## Configuration

Everything is optional — the package renders nothing until you (or a resolver)
provide values. The published `config/basekit-laravel-seo.php` controls:

- `enabled` — master switch; when `false` the head component renders nothing
  and the sitemap/robots routes are not registered.
- `defaults` — site name, title suffix, description, Open Graph image and
  card, locale.
- `canonical` — `base_url` and `trusted_hosts` for canonical URL resolution.
- `robots` — `/robots.txt` directives (`user_agents`, `allow`, `disallow`,
  `sitemap`).
- `sitemap` — route `path`, splitting limits (`max_urls`, `max_bytes`) and
  caching (`enabled`, `ttl`, `store`).
- `views` — the `head` Blade template used by the component.

## Extending and customizing

- **SEO resolvers** — implement `SeoResolver` (`supports()` / `resolve()`) and
  bind it under `SeoManager::RESOLVER_TAG` to provide metadata for your
  content.
- **Sitemap providers** — implement `SitemapProvider` (`entries()`) and bind it
  under `SitemapProvider::PROVIDER_TAG` to contribute URLs.
- **Views** — publish the views (`php artisan vendor:publish --tag="basekit-laravel-seo-views"`)
  and edit the partials, or point the `views.head` config key at your own Blade
  template.

## Security

- Canonical, Open Graph, Twitter, alternate and sitemap URLs are validated
  (scheme allow-list, no credentials/fragments/control characters).
- JSON-LD is serialized with hex-escaping so untrusted values cannot escape the
  `<script>` element; sitemap values are XML-escaped.
- robots.txt directive values are cut at the first line break or control
  character so they cannot inject extra directives.
- No URL is ever built from the raw `Host` header.

## What this package does not do

- It does not know your content. Site-specific data flows in exclusively
  through `SeoResolver` and `SitemapProvider` implementations.
- It has no database, no Eloquent, Livewire or Filament dependency and no admin
  UI; state lives in the request scope and (for sitemaps) the Laravel cache.
- It does not ship video/image/news domain sitemaps or Google extensions, and
  `hreflang` alternates are never auto-generated.

## Documentation

Full documentation is published at
<https://basekit-laravel.github.io/basekit-laravel-seo/>:

- [Getting started](https://basekit-laravel.github.io/basekit-laravel-seo/guide/getting-started.html)
- [Page metadata](https://basekit-laravel.github.io/basekit-laravel-seo/guide/page-metadata.html)
- [Structured data (JSON-LD)](https://basekit-laravel.github.io/basekit-laravel-seo/guide/structured-data.html)
- [Resolvers](https://basekit-laravel.github.io/basekit-laravel-seo/guide/resolvers.html)
- [XML sitemap](https://basekit-laravel.github.io/basekit-laravel-seo/guide/sitemap.html)
- [robots.txt](https://basekit-laravel.github.io/basekit-laravel-seo/guide/robots.html)
- [Configuration](https://basekit-laravel.github.io/basekit-laravel-seo/guide/configuration.html)

## Testing

Package development uses Pest, PHPStan/Larastan (level 6) and Laravel Pint:

```bash
composer test          # vendor/bin/pest
composer analyse       # vendor/bin/phpstan analyse
composer format        # vendor/bin/pint
composer lint          # vendor/bin/pint --test
```

See [docs/development](docs/development) in the repository for the maintainer
planning documents, and `docs/package.json` for the docs site workflow.

## License

The package is open-sourced software licensed under the [MIT license](LICENSE).