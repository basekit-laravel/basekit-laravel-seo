## Getting started

### Install

```bash
composer require basekit-laravel/basekit-laravel-seo
```

Package discovery registers the service provider; there is nothing to
register by hand.

Publish the config so you can adjust it:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-config"
```

Optionally publish the Blade views when you want to customize them:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-views"
```

### Render the head

Add the component inside the `<head>` of your layout:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-basekit-laravel-seo::head />
</head>
```

Until you set metadata, it renders nothing.

### Set metadata

Use the `seo()` helper from a controller:

```php
use App\Models\Article;
use BasekitLaravel\BasekitLaravelSeo\Support\ArticleSchema;

public function show(Article $article)
{
    seo()
        ->title($article->title)
        ->description($article->excerpt)
        ->canonicalUrl(route('articles.show', $article))
        ->schema(ArticleSchema::make()->headline($article->title));

    return view('articles.show', ['article' => $article]);
}
```

Next steps:

- [Page metadata](./page-metadata) — every tag group.
- [Structured data](./structured-data) — the JSON-LD builders.
- [Resolvers](./resolvers) — let your content provide its own SEO.
- [XML sitemap](./sitemap) — sitemap providers and `/sitemap.xml`.
- [robots.txt](./robots) — the robots endpoint.
- [Configuration](./configuration) — full config reference.

The sitemap and robots routes are registered automatically:

```text
GET /sitemap.xml
GET /robots.txt
```
