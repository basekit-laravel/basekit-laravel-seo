# Basekit Laravel SEO

[![CI](https://github.com/basekit-laravel/basekit-laravel-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/basekit-laravel/basekit-laravel-seo/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/basekit-laravel/basekit-laravel-seo.svg)](https://packagist.org/packages/basekit-laravel/basekit-laravel-seo) [![License](https://img.shields.io/packagist/l/basekit-laravel/basekit-laravel-seo.svg)](LICENSE) [![PHP](https://img.shields.io/packagist/php-v/basekit-laravel/basekit-laravel-seo.svg)](https://packagist.org/packages/basekit-laravel/basekit-laravel-seo) [![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20.svg)](https://laravel.com)
[![Docs](https://img.shields.io/badge/docs-basekit--laravel--seo-blue.svg)](https://basekit-laravel.github.io/basekit-laravel-seo/)

SEO metadata, structured data, robots.txt and XML sitemaps for Laravel.

Set page metadata from your application and render it with a single Blade component. The package supports standard metadata, Open Graph, Twitter/X, `hreflang`, JSON-LD, robots.txt and XML sitemaps.

Nothing is stored in the database, and values that you do not provide are simply omitted.

## Requirements

- PHP 8.4 or 8.5
- Laravel 13

## Installation

Install the package:

```bash
composer require basekit-laravel/basekit-laravel-seo
```

Package discovery registers the service provider automatically.

Publish the configuration if you want to change the defaults:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-config"
```

## Usage

Add the head component to your layout:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-basekit-laravel-seo::head />
</head>
```

Then set metadata from a controller or anywhere else in the request:

```php
public function show(Article $article)
{
    seo()
        ->title($article->title)
        ->description($article->excerpt)
        ->canonicalUrl(route('articles.show', $article));

    return view('articles.show', [
        'article' => $article,
    ]);
}
```

The head component renders the values that were set and leaves everything else out.

## Metadata

The `seo()` helper provides a fluent API for page metadata:

```php
seo()
    ->title('About us')
    ->description('Learn more about our company.')
    ->canonicalUrl(route('about'))
    ->robots('index, follow')
    ->locale('en_US');
```

It also supports:

- Open Graph metadata
- Twitter/X metadata
- `hreflang` alternates
- JSON-LD structured data

Explicit values override resolver and configuration defaults.

## Resolvers

For models or other content types that know how their SEO metadata should be built, create a resolver implementing `SeoResolver`.

```php
seo()->for($article);
```

The first registered resolver that supports the subject provides its metadata. You can still override individual values afterwards:

```php
seo()
    ->for($article)
    ->title('Custom title');
```

## Structured data

JSON-LD can be added through the `schema()` API.

The package includes builders for:

- `WebSite`
- `WebPage`
- `Article`
- `BlogPosting`
- `Organization`

Plain arrays are also supported.

## Open Graph and Twitter/X

Open Graph and Twitter/X metadata are rendered by the head component together with the standard page metadata.

Configure defaults globally or set values for individual requests through the `seo()` helper or a resolver.

## Canonical URLs

Canonical URLs can be set explicitly:

```php
seo()->canonicalUrl(
    route('articles.show', $article)
);
```

When resolving canonical URLs automatically, the package uses the configured canonical base URL, then `app.url`, and finally an allow-listed request host.

The raw `Host` header is never used directly.

## Alternate languages

Add `hreflang` alternates with:

```php
seo()->alternate('de', 'https://example.com/de/about');
seo()->alternate('en', 'https://example.com/en/about');
```

Alternates are not generated automatically.

## robots.txt

The package serves:

```text
/robots.txt
```

Its contents are configured through `config/basekit-laravel-seo.php`.

The sitemap URL can be included automatically using the canonical site origin.

## XML sitemap

The package serves:

```text
/sitemap.xml
```

Register `SitemapProvider` implementations to contribute URLs.

Large sitemaps are automatically split according to the configured URL or byte limits:

```text
/sitemap.xml
/sitemap-1.xml
/sitemap-2.xml
```

When splitting is required, `/sitemap.xml` becomes a sitemap index.

Sitemap output is cached so providers only run on a cache miss. Concurrent
requests on a cold cache take a short-lived lock, so only one rebuilds the
sitemap.

Warm it after a deploy, and clear it when your content changes:

```bash
php artisan basekit-seo:sitemap:warm
php artisan basekit-seo:sitemap:clear
```

The equivalent API for application code (for example in a model observer):

```php
app(SitemapCache::class)->clear();
app(SitemapGenerator::class)->regenerateNow();
```

### HTTP caching

The sitemap and robots.txt endpoints send `Cache-Control`, an `ETag` and
`X-Content-Type-Options: nosniff`, and answer `304 Not Modified` to conditional
requests, so a CDN can serve repeat crawls without hitting your application.

Freshness is controlled by `sitemap.cache.ttl` and `robots.cache_ttl`; setting
either to `0` disables caching for that endpoint.

## Configuration

The published configuration controls:

- package enable/disable state
- default metadata
- title suffix
- default Open Graph image
- canonical URL handling
- trusted hosts
- robots.txt contents and cache lifetime
- sitemap routes, limits, caching and route middleware
- Blade views

All defaults are optional.

Values the package cannot render a correct result with — such as
`sitemap.max_urls` outside `1`–`50000` — are rejected while the service provider
boots, so a misconfiguration fails the deploy instead of breaking a crawler
later.

By default the package's routes carry no middleware. Add throttling yourself if
you want it:

```php
'sitemap' => [
    'middleware' => ['throttle:60,1'],
],
```

## Custom views

Publish the package views:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-views"
```

You can then modify the provided templates or configure the package to use your own head view.

## Security

URLs used for canonical links, Open Graph, Twitter/X, alternates and sitemaps are validated before output.

The package also escapes JSON-LD, XML, Open Graph and Twitter/X output and prevents robots.txt values from injecting additional directives.

Nothing is ever built from the raw `Host` header: sitemap index locations and the robots.txt `Sitemap:` declaration come from a trusted canonical origin only. A split sitemap refuses to render at all when no trusted origin can be established, rather than inventing one.

CI runs `composer audit` on every push, so a newly published advisory in the dependency tree fails the build.

## Limitations

The package does not discover your application's content automatically.

Use `SeoResolver` implementations for page metadata and `SitemapProvider` implementations for sitemap entries.

The package does not provide:

- database storage
- an admin interface
- Eloquent-specific SEO models
- automatically generated `hreflang` links
- image, video or news sitemaps

## Documentation

Full documentation:

https://basekit-laravel.github.io/basekit-laravel-seo/

It covers:

- getting started
- page metadata
- structured data
- resolvers
- XML sitemaps
- robots.txt
- HTTP caching
- console commands
- configuration
- deployment (including Laravel Octane)

## Testing

```bash
composer test           # Pest
composer test-coverage  # Pest with a coverage report in build/coverage
composer analyse        # PHPStan / Larastan
composer lint           # Laravel Pint (check only)
composer format         # Laravel Pint (apply fixes)
composer audit          # Known dependency vulnerabilities
```

## License

MIT.
