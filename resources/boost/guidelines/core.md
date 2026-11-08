## Basekit Laravel SEO (`basekit-laravel/basekit-laravel-seo`)

Centralized SEO metadata, Open Graph, Twitter cards, JSON-LD structured data, robots.txt, and chunked XML sitemaps. Deliberately contains no database tables, UI components, or admin screens.

- **Layer:** Foundation (see DECISIONS.md section 8)
- Requirements: PHP `^8.4|^8.5`, Laravel `^13` (`illuminate/contracts`, `illuminate/http`, `illuminate/support`, `illuminate/view`)

### Key classes and entry points
- `SeoManager`: scoped service (and `seo()` helper) managing metadata, Open Graph, Twitter, robots, alternates, and schemas.
- `SeoData`: immutable value object holding resolved metadata for page rendering.
- `SeoResolver`: contract for domain objects (Page, Post, etc.) to resolve their own SEO data.
- `SitemapProvider`: contract for packages/apps to yield sitemap entries (`SitemapEntry`).
- `Head`: Blade component (`<x-basekit-laravel-seo::head />`) rendering head tags, Open Graph, and JSON-LD script blocks.
- Routes: `/sitemap.xml` (or chunked `/sitemap-{n}.xml`) and `/robots.txt`.

### Usage
```blade
{{-- In layout <head> --}}
<x-basekit-laravel-seo::head />
```
```php
// In controller or view composer:
seo()->title('About Us')
    ->description('Learn about our company')
    ->alternate('es', url('/es/acerca-de'));
```

### Commands and configuration
- Commands: `basekit-seo:sitemap:clear`, `basekit-seo:sitemap:warm {--force}`.
- Config file: `config/basekit-laravel-seo.php`.
- Key options: `enabled` (`true`), `defaults.title`, `defaults.site_name`, `canonical.origin`, `sitemap.path`, `sitemap.chunking.max_urls`.
- Publish tags: `basekit-laravel-seo-config`, `basekit-laravel-seo-views`.

### Conventions
- Use `seo()` helper or inject `SeoManager` to configure page-level metadata.
- Tags and alternates are locale-aware (`alternate(string $locale, string $url)`).
- Tag custom resolvers with `basekit-laravel-seo.resolvers` in the service container.
- Tag sitemap providers with `basekit-laravel-seo.sitemap_providers`.
- Dependencies point to lower layers only; no admin UI in this package.
- Test with at least two locales (for example `en`, `es`).

### Do not
- Do not build canonical URLs or sitemap links from untrusted request `Host` headers; set `canonical.origin`.
- Do not register raw HTML strings for JSON-LD; use `Schema` builder or structured arrays so output is properly escaped.
- Do not define database tables or migrations in this package.
