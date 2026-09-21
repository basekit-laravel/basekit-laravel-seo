# Rendering the SEO head

The package ships a framework-native Blade component that renders the resolved
metadata:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-basekit-laravel-seo::head />

    {{-- ...rest of your head... --}}
</head>
```

The component is a *pure renderer*: it asks the `seo()` manager for the resolved
`SeoData` and emits the corresponding tags. It contains no resolution logic —
point it at resolved data via `:data` or let it resolve from the manager. It
never invents tags: any value that is `null` (meaning "not provided") is simply
omitted, so an empty/default page renders nothing misleading.

## Setting the metadata

Use the fluent `seo()` manager anywhere before the head is rendered — typically
from a controller or route middleware:

```php
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

seo()
    ->title('Services')
    ->description('What we do and how we work.')
    ->canonicalUrl('https://acme.test/services')
    ->robots('index, follow')
    ->alternate('en', 'https://acme.test/en/services')
    ->alternate('de', 'https://acme.test/de/services')
    ->alternate('x-default', 'https://acme.test/services')
    ->openGraph([
        'title' => 'Services',
        'type' => 'website',
        'image' => 'https://acme.test/og.png',
        'site_name' => 'Acme',
    ])
    ->twitter([
        'card' => 'summary_large_image',
        'site' => '@acme',
        'title' => 'Services',
    ])
    ->schema(WebPageSchema::make()->name('Services'));
```

For content-driven pages, resolve from a resolver first:

```php
seo()->for($article); // tags from the matching SeoResolver, pre-merged
```

Or short-circuit resolution entirely by passing a ready-made `SeoData`:

```blade
<x-basekit-laravel-seo::head :data="$seo" />
```

The `:data` attribute accepts a `BasekitLaravel\BasekitLaravelSeo\SeoData`
instance only; with no attribute the component resolves from the container
manager (`<x-basekit-laravel-seo::head />`).

## Rendering order

Tags are emitted in a fixed order:

1. `<title>`
2. `<meta name="description">`
3. `<meta name="robots">` (page-level directives)
4. `<link rel="canonical">`
5. `<link rel="alternate" hreflang="...">` (in registration order)
6. `og:*` meta tags (title, description, type, url, image, site_name)
7. `twitter:*` meta tags
8. JSON-LD `application/ld+json` blocks (one per schema)

Each group is a separate partial under `resources/views/partials/`, included by
the head view.

## Title suffix

The title suffix is a rendering concern. Configure it globally:

```php
// config/basekit-laravel-seo.php
'defaults' => [
    'title_suffix' => 'Acme',
    // ...
],
```

`SeoData` never stores the formatted title — the component applies
`seo()->titleWithSuffix()` on demand and never duplicates the suffix (`Services
| Acme`, never `Services | Acme | Acme`). Titles without a configured suffix are
rendered untouched.

## Alternates and hreflang

`alternate($hreflang, $url)` emits a `<link rel="alternate">`. Languages are
BCP-47-ish (`en`, `en-gb`, `hu`) and `x-default` is supported for the
language-neutral fallback. The URLs are canonical-validated (http/https, no
fragments, no credentials). Alternates are never auto-generated — the package
has no locale/domain knowledge, so you register what your site actually serves.

## Structured data (JSON-LD)

Every registered schema renders as its own `<script
type="application/ld+json">` block, serialized with the hardened flag set
(`JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG |
JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR`). Angle
brackets and quotes inside values are hex-escaped, so a value such as
`</script><script>alert(1)</script>` can never terminate the script element.

Register any number of schemas:

```php
seo()->schema(WebPageSchema::make()->name('Services'));
seo()->schema(OrganizationSchema::make()->name('Acme')->logo('https://acme.test/logo.png'));
```

Plain arrays are also accepted and serialized with the same hardened flags, but
still must be schema.org-shaped objects (include `@type`).

## Disabling the package

The `enabled` config flag (default `true`) controls the renderer with **no
partial output and no throw**: when disabled, `<x-basekit-laravel-seo::head />`
emits nothing — even with an explicit `:data` — so a disabled package never
leaks misleading or half-rendered metadata.

## Customizing the views

The component's view and partials live under `resources/views/` and are exposed
through the `basekit-laravel-seo` namespace. There are two override paths:

### 1. Publish the package views

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-views"
```

This copies the view tree to `resources/views/vendor/basekit-laravel-seo`,
which takes precedence over the packaged views. Edit any partial — e.g. replace
`partials/title.blade.php` to change how the title is emitted. The sitemap view
publishes through the same tag without conflict.

### 2. Point the `views.head` config at your own main template

```php
// config/basekit-laravel-seo.php
'views' => [
    'sitemap' => 'seo.sitemap',
    'head' => 'components.seo-head', // your own Blade template
],
```

Your template receives the same variables as the packaged one: `$seo`
(`SeoData`), `$title` (`?string`, suffix applied), `$twitterCard` (`?string`),
and `$schemas` (`array<int,string>` of pre-escaped JSON-LD blocks). You can
include the packaged partials from it:

```blade
@include('basekit-laravel-seo::partials.title')
@include('basekit-laravel-seo::partials.description')
{{-- ... --}}
```

## Security notes

- All attribute and text values are emitted through Blade `{{ }}`
  (`htmlspecialchars`), never raw `{{!! !!}}` output for metadata.
- The one deliberate raw output — the JSON-LD script blocks — is pre-escaped
  in PHP with the hardened serializer before it reaches the template, so the
  `{!! !!}` there cannot be exploited.
- Canonical and alternate links are normalized by `CanonicalUrl`; the component
  never falls back to `request()->url()` or trusts the `Host` header, and it
  performs no canonical inference of its own.

See `docs/seo-data.md` and `docs/resolvers.md` for the model and resolution
pipeline this renderer speaks in terms of.