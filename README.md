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

- **Centralized metadata** — a single immutable `SeoData` model resolved from
  layered sources (config defaults → content resolvers → explicit overrides)
  and rendered as `<head>` tags by a Blade component.
- **Structured data** — hardened JSON-LD builders (`WebSite`, `WebPage`,
  `Article`, `Organization`) that cannot break out of their `<script>` element.
- **Robot directives** — page-level `<meta name="robots">` and a `robots.txt`
  endpoint whose directives are immune to line injection.
- **XML sitemaps** — entries aggregated from tagged providers, split into
  standard-sized documents with a deterministic sitemap index, and cached in
  the Laravel cache.
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

## What this package does not do

- It does not know your content. Site-specific data flows in exclusively
  through `SeoResolver` and `SitemapProvider` implementations.
- It does not store anything. There is no database table, no admin/UI and no
  queue worker; state lives in the request scope and (for sitemaps) the
  Laravel cache.
- It does not ship video/image/news domain sitemaps or Google extensions, and
  `hreflang` alternates are never auto-generated.

## Development

Package development uses Pest, PHPStan/Larastan (level 6) and Laravel Pint:

```bash
composer install
composer test          # vendor/bin/pest
composer analyse       # vendor/bin/phpstan analyse
composer format        # vendor/bin/pint
composer lint          # vendor/bin/pint --test
```

See [docs/development](docs/development) in the repository for the maintainer
planning documents, and `docs/package.json` for the docs site workflow.

## License

The package is open-sourced software licensed under the [MIT license](LICENSE).