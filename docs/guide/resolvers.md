## Resolvers

A resolver builds SEO metadata for your own content — an Eloquent model, a
page class, anything. The package itself never needs to know what that
content is.

### Example

Take an `Article` model with a title, an excerpt and a published state.

**1. Implement the contract:**

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
        if (! $subject instanceof Article || ! $subject->isPublished()) {
            return null;
        }

        return SeoData::make()
            ->withTitle($subject->title)
            ->withDescription($subject->excerpt)
            ->withCanonicalUrl(route('articles.show', $subject))
            ->withRobots($subject->isPublic() ? 'index, follow' : 'noindex');
    }
}
```

**2. Register it** by tagging the class in any service provider:

```php
use BasekitLaravel\BasekitLaravelSeo\SeoManager;

public function register(): void
{
    $this->app->tag(ArticleSeoResolver::class, SeoManager::RESOLVER_TAG);
}
```

**3. Point the manager at your content:**

```php
public function show(Article $article)
{
    seo()->for($article);

    return view('articles.show', ['article' => $article]);
}
```

The head component now renders that metadata. You can still override
individual values per request:

```php
seo()->for($article)->title('Read this first')->data();
```

### How it works

- `supports()` decides whether this resolver handles a given subject.
- `resolve()` returns the metadata, or `null` when nothing applies (the
  resolver above skips unpublished articles).
- The first resolver whose `supports()` returns `true` wins; tag order
  defines precedence.
- Resolver values sit above the config defaults; `seo()` calls override them.
- Canonical and alternate URLs are validated before they are used
  (http/https only, no credentials or fragments).

The metadata you build is the same `SeoData` described on the
[Page metadata](./page-metadata) page — `withDescription`,
`withOpenGraph([...])`, `withTwitter([...])`, etc.
