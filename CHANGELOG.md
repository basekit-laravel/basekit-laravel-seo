# Changelog

All notable user-visible changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 (2026-09-22)


### Features

* add SEO core foundation for centralized metadata ([cf879e5](https://github.com/basekit-laravel/basekit-laravel-seo/commit/cf879e5424440dbbf7280abd3604c1a5df542268))
* add SEO head rendering layer ([37cba3c](https://github.com/basekit-laravel/basekit-laravel-seo/commit/37cba3c5756fca2ed7ff5679371f2b371e937060))
* add sitemap splitting, index and caching ([2e18c07](https://github.com/basekit-laravel/basekit-laravel-seo/commit/2e18c07540569e04dab877946894142f7b07c966))
* add XML sitemap and robots.txt routes ([17168e7](https://github.com/basekit-laravel/basekit-laravel-seo/commit/17168e7524a489f920d4d8c2969a5939e1e7719a))

## [Unreleased]

Initial implementation of the package. There is no tagged release yet; version
numbers start when the first release is tagged.

### Added

- **SEO core** — immutable `SeoData` value object (`make()`, `fromArray()`,
  `toArray()`, `jsonSerialize()`, immutable `with*()` withers) and the fluent
  `SeoManager` (`seo()` helper) resolving metadata from three layers: config
  defaults, the first supporting `SeoResolver`, and explicit overrides.
- **Resolution** — `SeoResolver` contract (`supports()` / `resolve()`),
  discovered through the `basekit-laravel-seo.resolvers` container tag; first
  supporter wins; lists concatenate; Open Graph/Twitter merge field-by-field.
- **Head rendering** — `<x-basekit-laravel-seo::head />` Blade component
  emitting title, description, robots, canonical, `hreflang` alternates, Open
  Graph, Twitter and JSON-LD in a fixed order; renders nothing when the
  package is disabled.
- **Structured data** — hardened `Schema` serializer (`JSON_HEX_*` flags) with
  `WebSite`, `WebPage`, `Article` and `Organization` builders; plain arrays
  accepted with the same escaping.
- **Canonical URLs** — `CanonicalUrl` value object (http/https only, no
  credentials/fragments/control characters, host normalized) and a
  `CanonicalUrlResolver` preferring a configured `base_url`, then `app.url`,
  then an allow-listed request host.
- **Metadata value objects** — `Title` (suffix, deduplicated), `RobotsMeta`
  (normalized directives with `INDEX`/`NOINDEX`/... constants), `OpenGraph`,
  `TwitterMeta`, and `Alternate` (BCP-47-ish + `x-default`).
- **robots.txt** — `/robots.txt` route built from config via the `Robots`
  builder; directive values sanitized against line injection; `Sitemap:`
  declaration derived from the trusted canonical origin.
- **XML sitemap** — `SitemapProvider` contract (`entries(): iterable`) tagged
  with `basekit-laravel-seo.sitemap-providers`; `SitemapEntry` value object
  with validated `loc`, `lastmod`, `changefreq` and `priority`; `/sitemap.xml`
  route.
- **Sitemap splitting and index** — aggregate output split at entry boundaries
  by `max_urls` (default 50,000) and `max_bytes` (default 50 MB, exact UTF-8
  byte accounting); split sites serve a `<sitemapindex>` plus deterministic
  `/sitemap-{n}.xml` chunk routes (`404` for missing chunks); index URLs built
  only from the trusted canonical origin.
- **Sitemap caching** — versioned cache keys with configurable store and TTL;
  providers run only on a cache miss; public `SitemapCache::clear()`
  invalidation API; transparent pass-through when caching is disabled.
- **Documentation** — VitePress documentation site in `docs/` and a GitHub
  Pages deployment workflow; root `README.md`.

### Security

- Canonical/OG/Twitter/alternate/sitemap URLs validated against unsafe
  schemes, credentials, fragments and control characters.
- JSON-LD serialization hex-escapes `<`, `>`, `&`, `'` and `"`.
- robots.txt directive values cut at the first line break/control character.
- Sitemap values XML-escaped and control characters stripped.
- No URL-building from the raw `Host` header anywhere.

### Notes

- When `basekit-laravel-seo.enabled` is `false`, the sitemap/robots routes are
  not registered and the head component renders nothing.
- Providers that throw or yield non-`SitemapEntry` values fail loudly; a
  single oversized entry raises `InvalidArgumentException`.
