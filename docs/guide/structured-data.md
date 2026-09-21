## Structured data (JSON-LD)

The package ships schema builders that render valid
`<script type="application/ld+json">` blocks. Register one (or more) with
`schema()`:

```php
seo()->schema(WebPageSchema::make()->name('Services'));
```

### Available builders

| Class | `@type` | Typical use |
| --- | --- | --- |
| `WebSiteSchema` | `WebSite` | The whole site. |
| `WebPageSchema` | `WebPage` | A page. |
| `ArticleSchema` | `Article` | A blog post / article. |
| `OrganizationSchema` | `Organization` | The publisher / company. |

### WebSite

```php
use BasekitLaravel\BasekitLaravelSeo\Support\WebSiteSchema;

seo()->schema(
    WebSiteSchema::make()
        ->name('Acme')
        ->url('https://acme.test'),
);
```

### WebPage with breadcrumbs

```php
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

seo()->schema(
    WebPageSchema::make()
        ->name('Services')
        ->description('What we do.')
        ->url('https://acme.test/services')
        ->isPartOf('Acme')
        ->breadcrumb([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://acme.test'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => 'https://acme.test/services'],
        ]),
);
```

### Article

`Article` covers blog posts; switch the `@type` with `type()` for
`BlogPosting` or `CreativeWork`:

```php
use BasekitLaravel\BasekitLaravelSeo\Support\ArticleSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\OrganizationSchema;

$publisher = OrganizationSchema::make()
    ->name('Acme')
    ->logo('https://acme.test/logo.png');

seo()->schema(
    ArticleSchema::make()
        ->type('BlogPosting')
        ->headline($article->title)
        ->description($article->excerpt)
        ->url(route('articles.show', $article))
        ->datePublished((string) $article->published_at)
        ->author($article->author->name)
        ->publisher($publisher),
);
```

### Organization

```php
seo()->schema(
    OrganizationSchema::make()
        ->name('Acme')
        ->url('https://acme.test')
        ->logo('https://acme.test/logo.png')
        ->sameAs(['https://twitter.com/acme', 'https://github.com/acme']),
);
```

### Plain arrays

Any schema object can be replaced by a plain array. It is serialized with the
same escaping but must stay schema.org-shaped (include `@type`):

```php
seo()->schema([
    '@type' => 'WebPage',
    'name' => 'Services',
    'url' => 'https://acme.test/services',
]);
```

### Why it is safe

Values are JSON-encoded with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS |
JSON_HEX_QUOT`, so a value such as `</script><script>alert(1)</script>`
cannot escape the surrounding `<script>` element.