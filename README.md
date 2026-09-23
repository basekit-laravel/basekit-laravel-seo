# Basekit Laravel SEO

Metadata, structured data (JSON-LD), robots.txt and XML sitemaps for Laravel
sites in the Basekit ecosystem.

Install the package, render one component in your layout, and set page
metadata from your controllers. Nothing is stored, and no value is invented —
whatever you (or a resolver) do not provide is left out.

| Component | Constraint |
| --- | --- |
| PHP | `^8.4` or `^8.5` |
| Laravel | `^13` |

## Features

- **Page metadata** — one immutable `SeoData` value object per request:
  title, description, canonical URL, robots, Open Graph, Twitter/X, locale,
  `hreflang` alternates and JSON-LD schemas.
- **`seo()` helper** — fluent access to the same data: `title()`,
  `description()`, `canonicalUrl()`, `robots()`, `openGraph()`, `twitter()`,
  `locale()`, `alternate()`, `schema()` and `for($subject)`.
- **Layered resolution** — values merge from config defaults, the first
  supporting `SeoResolver`, then your explicit calls. Explicit calls win;
  anything no layer sets is omitted.
- **Head component** — `<x-basekit-laravel-seo::head />` renders the
  resolved metadata: title (with configurable suffix), description,
  canonical, robots, Open Graph, Twitter/X, `hreflang` and JSON-LD.
- **Resolvers** — implement `SeoResolver` and tag it to describe your own
  content: models, page classes, anything.
- **Structured data** — JSON-LD builders for `WebSite`, `WebPage`, `Article`
  (including `BlogPosting`) and `Organization`. Plain arrays are accepted
  with the same escaping.
- **Canonical URLs** — resolved from `canonical.base_url`, then `app.url`,
  then an allow-listed request host. The raw `Host` header is never used.
- **robots.txt** — `/robots.txt` served from configuration, with the
  `Sitemap:` line derived from the canonical origin.
- **XML sitemap** — `/sitemap.xml` built from tagged `SitemapProvider`
  classes. Output over `max_urls` or `max_bytes` is split into
  `/sitemap-{n}.xml` documents behind a sitemap index, and cached until
  `SitemapCache::clear()` is called.

## Installation

```bash
composer require basekit-laravel/basekit-laravel-seo
```

Package discovery registers the service provider. Publish and review the
configuration:

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

For content types that own their metadata, implement `SeoResolver`; for
sitemap URLs, implement `SitemapProvider`. The package also serves
`/sitemap.xml`, `/sitemap-{n}.xml` (when split) and `/robots.txt` on its own.

## Configuration

Everything is optional — the head renders nothing until you (or a resolver)
provide values. The published `config/basekit-laravel-seo.php` controls:

- `enabled` — master switch; when `false`, the head component renders nothing
  and the sitemap/robots routes are not registered.
- `defaults` — site name, title suffix, description, Open Graph image and
  card, locale.
- `canonical` — `base_url` and `trusted_hosts` for canonical URL resolution.
- `robots` — `/robots.txt` directives (`user_agents`, `allow`, `disallow`,
  `sitemap`).
- `sitemap` — route `path`, splitting limits (`max_urls`, `max_bytes`) and
  caching (`enabled`, `ttl`, `store`).
- `views` — the `head` Blade template used by the component.

## Extending

- **Resolvers** — implement `SeoResolver` (`supports()` / `resolve()`) and
  tag it with `SeoManager::RESOLVER_TAG` to provide metadata for your content.
- **Sitemap providers** — implement `SitemapProvider` (`entries()`) and tag
  it with `SitemapProvider::PROVIDER_TAG` to contribute URLs.
- **Views** — publish them (`--tag="basekit-laravel-seo-views"`) and edit the
  partials, or point `views.head` at your own Blade template.

## Security

- Canonical, Open Graph, Twitter, alternate and sitemap URLs are validated:
  http/https only, no credentials, fragments or control characters.
- JSON-LD is hex-escaped so values cannot break out of the `<script>` element;
  sitemap values are XML-escaped.
- robots.txt values are cut at the first line break or control character, so
  they cannot inject extra directives.
- No URL is built from the raw `Host` header.

## Limitations

- The package does not know your content. It enters only through
  `SeoResolver` and `SitemapProvider` implementations.
- No database, no Eloquent, Livewire or Filament dependency, no admin UI.
  State lives in the request (plus the Laravel cache for sitemaps).
- No video/image/news sitemaps or Google extensions, and `hreflang`
  alternates are never auto-generated.

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

```bash
composer test          # vendor/bin/pest
composer analyse       # vendor/bin/phpstan analyse
composer format        # vendor/bin/pint
composer lint          # vendor/bin/pint --test
```

## License

Licensed under the [MIT license](LICENSE).
