# robots.txt

The package serves a robots.txt at:

```text
GET /robots.txt
```

with a `text/plain` content type, built by the existing
`BasekitLaravel\BasekitLaravelSeo\Support\Robots` builder. The route adds the
standard output + the sitemap declaration; there is no second robots
implementation.

## Configuration

All directives come from the package config:

```php
// config/basekit-laravel-seo.php
'robots' => [
    'user_agents' => ['*'],
    'allow' => ['/'],
    'disallow' => [],
    'sitemap' => null,
],
```

A typical generated document is:

```text
User-agent: *
Allow: /

Sitemap: https://example.test/sitemap.xml
```

- `user_agents`, `allow`, `disallow` are emitted verbatim (sanitized), in
  order.
- `sitemap`, when explicitly configured, is emitted as given.
- When `sitemap` is `null`, the declaration is derived from the package's
  trusted canonical origin (see below) plus the sitemap route path, so the two
  always agree. The path itself is configurable through
  `basekit-laravel-seo.sitemap.path` (default `/sitemap.xml`); a split site
  still only exports the entry point here.

## Sitemap declaration and host poisoning

The derived `Sitemap:` URL never comes from a raw HTTP `Host` header. It uses
the same single trusted-origin resolution as the canonical URL default (and
sitemap links): `canonical.base_url`, then `app.url`, then — only if the
request host is listed in `canonical.trusted_hosts` — the request host.

This means a request carried out under a poisoned host such as
`Host: attacker.example` still declares `Sitemap: https://example.test/sitemap.xml`
(when `https://example.test` is the configured origin), and never references the
attacker-controlled host. When no safe origin can be established — no
`base_url`, no `app.url`, no allow-listed host — the `Sitemap:` line is simply
omitted rather than invented.

## Directive safety

The `Robots` builder maintains its Phase 1 control-character protection: every
directive value is cut at the first line break or control character, so an
attacker-supplied newline inside a rule cannot inject extra
`Allow:`/`Disallow:`/`Sitemap:` lines. This protection applies to the route's
config-driven output and to direct API use alike.

## Disabling the routes

When `basekit-laravel-seo.enabled` is `false`, the sitemap and robots routes
are not registered at boot, so `/sitemap.xml` and `/robots.txt` respond `404`.
This mirrors the head component, which renders nothing while disabled — a
disabled package never exposes partially configured SEO output.

## Customizing

There is intentionally no free-form directive API in this phase. The supported
directives are `User-agent`, `Allow`, `Disallow` and `Sitemap`, all driven by
config to keep unauthorized line injection impossible by construction.