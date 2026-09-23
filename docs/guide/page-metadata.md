## Page metadata

Everything the package can render lives on one immutable `SeoData` object.
You build it through the `seo()` manager.

### Basic example

```php
seo()
    ->title('Services')
    ->description('What we do and how we work.')
    ->canonicalUrl('https://acme.test/services');
```

Emits:

```html
<title>Services</title>
<meta name="description" content="What we do and how we work.">
<link rel="canonical" href="https://acme.test/services">
```

### Open Graph and Twitter

```php
seo()
    ->openGraph([
        'title' => 'Services',
        'type' => 'website',
        'image' => 'https://acme.test/og.png',
        'site_name' => 'Acme',
    ])
    ->twitter([
        'card' => 'summary_large_image',
        'site' => '@acme',
        'image' => 'https://acme.test/og.png',
    ]);
```

### Robots directives

Page-level robots control (shown as `<meta name="robots">`):

```php
seo()->robots('noindex, nofollow');
// or an array:
seo()->robots(['noindex', 'nofollow']);
```

### hreflang alternates

Add one `alternate()` call per language. `x-default` is supported:

```php
seo()
    ->alternate('en', 'https://acme.test/en/services')
    ->alternate('de', 'https://acme.test/de/services')
    ->alternate('x-default', 'https://acme.test/services');
```

Emits a `<link rel="alternate" hreflang="...">` per call.

### Structured data

Register JSON-LD schemas with `schema()` — see the
[Structured data](./structured-data) page:

```php
seo()->schema(WebPageSchema::make()->name('Services'));
```

### Title suffix

A global suffix (e.g. the site name) is applied on rendering, never duplicated:

```php
// config/basekit-laravel-seo.php
'defaults' => ['title_suffix' => 'Acme'],
```

`Title` + `Acme` renders as `Title | Acme`. Get the formatted title anywhere
with `seo()->titleWithSuffix()`.

### Rendering a ready-made SeoData

Pass a prepared `SeoData` straight to the component instead of resolving:

```blade
<x-basekit-laravel-seo::head :data="$seo" />
```

### Where values come from

`seo()` merges three layers, lowest first:

1. Config defaults (site name, description, locale, ...).
2. The matching resolver, when you call `seo()->for($model)`.
3. Your explicit calls to the fluent methods above.

Explicit values win. Open Graph and Twitter merge field by field; lists such
as alternates and schemas are combined. Anything no layer sets is omitted.
The default canonical URL comes from `canonical.base_url`, then `app.url`,
then an allow-listed request host — never the raw `Host` header.

### Disabling the package

With `enabled => false` the head component renders nothing and the sitemap /
robots routes are not registered.

### Customizing the head view

Publish the views (`basekit-laravel-seo-views` tag) and edit the partials under
`resources/views/vendor/basekit-laravel-seo/`, or point the `views.head` config
key at your own Blade template. Your template receives `$seo` (`SeoData`),
`$title`, `$twitterCard` and `$schemas`.
