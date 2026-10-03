## robots.txt

The package serves `/robots.txt` automatically, built from configuration.

### Configuration

```php
// config/basekit-laravel-seo.php
'robots' => [
    'user_agents' => ['*'],
    'allow' => ['/'],
    'disallow' => ['/private'],
    'sitemap' => null,     // null = derive from the canonical origin
    'cache_ttl' => 3600,   // HTTP freshness in seconds; 0 disables caching
],
```

With the defaults (besides `disallow`) the response is:

```text
User-agent: *
Allow: /

Sitemap: https://example.test/sitemap.xml
```

- `user_agents`, `allow`, `disallow`, `sitemap` are emitted in that order.
- When `sitemap` is `null`, it is derived from the trusted canonical origin
  (see `canonical.base_url` / `app.url`) plus the configured sitemap path, so
  the declaration always matches the actual route.
- A configured `sitemap` value is emitted as given.

### HTTP caching

`/robots.txt` is served with `Cache-Control: public, max-age=<cache_ttl>`, a
strong `ETag` and `X-Content-Type-Options: nosniff`, and answers `304 Not
Modified` to conditional requests. Because robots.txt changes rarely, a day is a
more useful default than an hour:

```php
'robots' => [
    'cache_ttl' => 86_400,
],
```

Set it to `0` to send no caching headers at all. See
[HTTP caching](/guide/caching).

### Disabling the routes

When `enabled => false`, the sitemap and robots routes are not registered —
both respond `404`.

### Safety

- Directive values (from config or direct API use) are cut at the first line
  break or control character, so a value containing a newline cannot inject
  extra `Allow:`/`Disallow:`/`Sitemap:` lines.
- The `Sitemap:` URL is never built from the raw `Host` header — only from the
  trusted canonical origin.