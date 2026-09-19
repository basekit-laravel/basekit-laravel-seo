# SeoData and the resolution pipeline

This package exposes a single immutable metadata model, `SeoData`, and a
`seo()` manager that resolves it from layered sources.

## SeoData

`BasekitLaravel\BasekitLaravelSeo\SeoData` is a `final readonly` value object.
Every value is nullable and every `with*()` method returns a **new** instance;
the original is never mutated.

| Property | Type | Meaning |
| --- | --- | --- |
| `title` | `?string` | Page `<title>`. |
| `description` | `?string` | Meta description. |
| `canonicalUrl` | `?CanonicalUrl` | Safe, normalized canonical URL. |
| `robots` | `?RobotsMeta` | Page-level robots directives. |
| `openGraph` | `?OpenGraph` | Open Graph metadata. |
| `twitter` | `?TwitterMeta` | Twitter/X card metadata. |
| `locale` | `?string` | Page locale (defaults to the site locale). |
| `alternates` | `list<Alternate>` | `hreflang` alternates. |
| `schemas` | `list<Schema\|array>` | JSON-LD `Schema` objects or plain arrays. |

### Null semantics

`null` means **"not provided by this layer"**. `SeoData` is a snapshot; it does
not invent values. Resolution (deciding which layer wins) is the manager's job,
described below. Because `null` is significant, a wither can also *clear* a
value: `SeoData::make()->withTitle('Draft')->withTitle(null)` yields a `null`
title.

### Construction

```php
use BasekitLaravel\BasekitLaravelSeo\SeoData;

$data = SeoData::make()
    ->withTitle('Services')
    ->withDescription('What we do.')
    ->withCanonicalUrl('https://acme.test/services')
    ->withRobots('noindex, nofollow')
    ->withOpenGraph(['title' => 'Services', 'site_name' => 'Acme'])
    ->withTwitter(['card' => 'summary_large_image'])
    ->withLocale('en')
    ->withAlternate('de', 'https://acme.test/de')
    ->withSchema($webPageSchema);

$array = $data->toArray();      // plain array, JSON-safe
$json  = json_encode($data);    // jsonSerialize() -> toArray()
$data2 = SeoData::fromArray($array); // round-trip (schemas become arrays)
```

Scalar/object values and lists compose: `withTitle()` replaces, `withAlternate()`
**appends**. Schema value objects become exported as plain arrays via
`toArray()` so the payload is always JSON-safe.

## Layered resolution

`seo()` resolves the final `SeoData` by merging **three layers**, lowest
priority first:

1. **Defaults** from `config('basekit-laravel-seo.defaults')` — description,
   locale, Open Graph `site_name`/`og_image`, and the default canonical URL.
2. **Resolver output** — the first `SeoResolver` tagged with
   `SeoManager::RESOLVER_TAG` whose `supports($subject)` returns `true`. See
   `docs/resolvers.md`.
3. **Explicit overrides** — values set directly through the fluent manager.

Merge rules:
- Scalar/object values: the first **non-null** value wins (defaults → resolver
  → explicit).
- Open Graph and Twitter merge **field by field**, so a resolver can override
  just the title while keeping the default `site_name`.
- Robots replaces the whole directive set (higher layer fully decides).
- Lists (`alternates`, `schemas`) **concatenate** across layers.

## The seo() helper

`seo()` is a global helper returning the container-bound `SeoManager`:

```php
$data = seo()
    ->for($article)                       // optional subject for resolvers
    ->title('Custom title')
    ->description('Custom description')
    ->canonicalUrl('https://acme.test/articles/1')
    ->data();
```

The manager is a scoped container binding, so it is fresh per request.
`seo()->reset()` discards accumulated state for long-running environments
(worker processes, tests).

## Canonical URL default

The default canonical comes from, in order:

1. `config('basekit-laravel-seo.canonical.base_url')`
2. `config('app.url')`
3. The request host **only if** it is listed in
   `config('basekit-laravel-seo.canonical.trusted_hosts')` (default `[]`).

When no safe base can be established, **no canonical URL is invented**.

## Title suffix

`title_suffix` is a rendering concern. `SeoData` never stores the formatted
title; use `seo()->titleWithSuffix()` to get the final title with the suffix
applied on demand. The suffix is not duplicated if the title already ends with
it.

## URL safety

Canonical URLs, Open Graph/Twitter images, and alternate locations are all
validated by `CanonicalUrl`:

- only `http`/`https` schemes, no credentials, no fragments,
- control characters rejected, host lowercased, default ports stripped,
- query strings preserved.

Unsafe values throw an `InvalidArgumentException`; use `CanonicalUrl::tryFrom()`
when a soft failure is preferred.