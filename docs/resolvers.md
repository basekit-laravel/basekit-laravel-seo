# SEO resolvers

Resolvers let site-specific content (pages, blog posts, products, custom model
types) provide SEO metadata **without this package knowing them**. They are the
only abstraction needed for ecosystem integration.

## The contract

Implement `BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver`:

```php
interface SeoResolver
{
    public function supports(mixed $subject): bool;

    public function resolve(mixed $subject): ?SeoData;
}
```

- `supports()` decides whether this resolver can produce metadata for the
  subject. Subjects can be anything — an Eloquent model, a plain object, a
  route, an array — resolvers decide for themselves.
- `resolve()` returns the metadata, or `null` when nothing applies.
- Resolvers must only return safe values: canonical and alternate URLs are
  validated by the package (`CanonicalUrl`) and unsupported schemes are
  rejected.

## Discovery

Resolver instances are resolved from the container through the tag
`SeoManager::RESOLVER_TAG` (`basekit-laravel-seo.resolvers`). The **first**
resolver whose `supports()` returns `true` wins; later resolvers are not
consulted, so tag order defines precedence.

Register your resolvers in a service provider:

```php
use BasekitLaravel\BasekitLaravelSeo\SeoManager;

public function register(): void
{
    $this->app->tag(ArticleSeoResolver::class, SeoManager::RESOLVER_TAG);
}
```

## Example

```php
use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\SeoData;

final class ArticleSeoResolver implements SeoResolver
{
    public function supports(mixed $subject): bool
    {
        return $subject instanceof Article;
    }

    public function resolve(mixed $subject): ?SeoData
    {
        if (! $subject instanceof Article) {
            return null;
        }

        return SeoData::make()
            ->withTitle($subject->title)
            ->withDescription(str($subject->body)->limit(160)->toString())
            ->withCanonicalUrl(route('articles.show', $subject))
            ->withOpenGraph(['title' => $subject->title, 'type' => 'article']);
    }
}
```

The resolved data is merged under the package defaults and can still be
overridden explicitly:

```php
seo()->for($article)->title('Read this first')->data();
```

## How it fits together

```
config defaults  →  first supporting resolver  →  explicit overrides  →  SeoData
```

See `docs/seo-data.md` for the full merge rules.