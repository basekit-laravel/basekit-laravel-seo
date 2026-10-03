## Configuration

Publish the config file and edit it:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-config"
```

This copies `basekit-laravel-seo.php` into your `config/` directory. The file
is commented in full; the less obvious options are explained below.

```php
return [

    // Master switch. When false, the head component renders nothing and the
    // /sitemap.xml, /sitemap-{n}.xml and /robots.txt routes are not registered.
    'enabled' => true,

    // Fallbacks used when a page provides nothing explicit.
    'defaults' => [
        'site_name' => 'Basekit',   // -> og:site_name
        'title_suffix' => '',       // appended to titles (never duplicated)
        'description' => '',
        'og_image' => null,         // default og:image (validated; dropped if unsafe)
        'twitter_card' => 'summary_large_image',
        'locale' => 'en',
    ],

    // Where the default canonical URL comes from.
    // base_url, then app.url, then the request host ONLY if it is in trusted_hosts.
    'canonical' => [
        'base_url' => null,     // e.g. https://example.test
        'trusted_hosts' => [],
    ],

    // /robots.txt contents.
    'robots' => [
        'user_agents' => ['*'],
        'allow' => ['/'],
        'disallow' => [],
        'sitemap' => null,      // null = derive from the canonical origin
        'cache_ttl' => 3600,    // HTTP freshness for /robots.txt; 0 disables
    ],

    // The sitemap endpoint and its scalability.
    'sitemap' => [
        'path' => '/sitemap.xml',   // chunks: /sitemap-1.xml, /sitemap-2.xml, ...
        'max_urls' => 50_000,       // 1 - 50000
        'max_bytes' => 50 * 1024 * 1024, // up to 50 MiB
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,           // 0 disables; also the HTTP max-age
            'store' => null,         // null = default cache store
        ],
        // Middleware applied to the sitemap and robots routes. Empty by default
        // so the package adds nothing to your route table implicitly. A good
        // default in production is 'throttle:60,1'.
        'middleware' => [],
    ],

    // The head view rendered by the head component.
    'views' => [
        'head' => 'basekit-laravel-seo::components.head',
    ],
];
```

### Environment variables

| Variable | Overrides |
| --- | --- |
| `BASEKIT_SEO_ENABLED` | `enabled` |
| `BASEKIT_SITE_NAME` | `defaults.site_name` |
| `BASEKIT_SEO_CANONICAL_URL` | `canonical.base_url` |
| `BASEKIT_SEO_SITEMAP_PATH` | `sitemap.path` |
| `BASEKIT_SEO_SITEMAP_CACHE` | `sitemap.cache.enabled` |

### Validation

Values the package cannot render a correct result with are rejected while the
service provider boots, so a misconfiguration fails the deploy instead of
breaking a crawler later. See [Deployment](/guide/deployment) for the full table.

### Route middleware

`sitemap.middleware` is applied to the `/sitemap.xml`, `/sitemap-{n}.xml` and
`/robots.txt` routes. It is empty by default so the package never adds
throttling to your route table unasked:

```php
'sitemap' => [
    'middleware' => ['throttle:60,1'],
],
```

Middleware can also be changed after the routes are loaded, for example in a
service provider's `boot()`:

```php
config()->set('basekit-laravel-seo.sitemap.middleware', ['throttle:60,1']);
```

### Notes

- **Title suffix** is applied at render time (`Title | Acme`, deduplicated),
  it is never stored in `SeoData`.
- **`views.head`** points at the template rendered by the head component. Point
  it at your own Blade template to fully control the output; it receives
  `$seo`, `$title`, `$twitterCard` and `$schemas`.
- **Splitting** happens at entry boundaries using the exact serialized byte
  size, so a served document never exceeds `max_bytes`.
- **`sitemap.cache.ttl` and `robots.cache_ttl`** set both the server-side cache
  lifetime and the HTTP `Cache-Control`/`ETag` freshness, so the two layers
  cannot drift apart. Set either to `0` to disable caching entirely. See
  [HTTP caching](/guide/caching).
