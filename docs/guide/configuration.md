## Configuration

Publish the config file and edit it:

```bash
php artisan vendor:publish --tag="basekit-laravel-seo-config"
```

This copies `basekit-laravel-seo.php` to your `config/` directory. It already
annotates every option. The non-obvious parts are explained below.

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
    ],

    // The sitemap endpoint and its scalability.
    'sitemap' => [
        'path' => '/sitemap.xml',   // chunks: /sitemap-1.xml, /sitemap-2.xml, ...
        'max_urls' => 50_000,
        'max_bytes' => 50 * 1024 * 1024, // 50 MiB
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
            'store' => null,        // null = default cache store
        ],
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

### Notes

- **Title suffix** is applied at render time (`Title | Acme`, deduplicated),
  it is never stored in `SeoData`.
- **`views.head`** points at the template rendered by the head component. Point
  it at your own Blade template to fully control the output; it receives
  `$seo`, `$title`, `$twitterCard` and `$schemas`.
- **Splitting** happens at entry boundaries using the exact serialized UTF-8
  size, so a served document is never larger than `max_bytes`.