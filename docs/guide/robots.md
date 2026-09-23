## robots.txt

The package serves `/robots.txt` automatically, built from configuration.

### Configuration

```php
// config/basekit-laravel-seo.php
'robots' => [
    'user_agents' => ['*'],
    'allow' => ['/'],
    'disallow' => ['/private'],
    'sitemap' => null, // null = derive from the canonical origin
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

### Disabling the routes

When `enabled => false`, the sitemap and robots routes are not registered —
both respond `404`.

### Safety

- Directive values (from config or direct API use) are cut at the first line
  break or control character, so a value containing a newline cannot inject
  extra `Allow:`/`Disallow:`/`Sitemap:` lines.
- The `Sitemap:` URL is never built from the raw `Host` header — only from the
  trusted canonical origin.