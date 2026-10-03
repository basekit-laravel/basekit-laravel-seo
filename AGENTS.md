# basekit-laravel-seo

This package provides Basekit Laravel SEO is a reusable, optional feature package for centralized metadata, structured data (JSON-LD), robots directives and XML sitemaps on Basekit-powered Laravel websites..

It targets PHP ^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
`basekit-laravel/basekit-laravel-seo`. All package source code lives under the `BasekitLaravel\BasekitLaravelSeo` namespace.

## What this project is

This repository is a **Laravel package**, not a standalone Laravel application. The package is
consumed by other Laravel applications, so the code must never assume application-only
scaffolding such as `app/`, a booted authentication system, `.env` files, or a local
`config/app.php`. Everything the package needs must come from its own service provider,
configuration, and published resources.

Keep this file focused on **this package**. For framework-level knowledge, consult the official
Laravel documentation, Laravel Boost (if configured), or the available documentation MCP tools —
do not embed generic Laravel guidance here.

## Repository structure

- `src/` — package source code. The service provider (`BasekitLaravel\BasekitLaravelSeo\BasekitLaravelSeoServiceProvider`) is
  registered automatically through Composer package discovery.
- `config/basekit-laravel-seo.php` — package configuration, published with
  `php artisan vendor:publish --tag="basekit-laravel-seo-config"`.
- `routes/web.php` — the sitemap and robots.txt routes. They are only registered
  when `enabled` is true, and they read `sitemap.middleware` at registration time.
- `resources/views/` — Blade views exposed under the `basekit-laravel-seo` view namespace
  (`view('basekit-laravel-seo::view-name')`).
- `src/Console/Commands/` — Artisan commands (`basekit-seo:sitemap:clear`,
  `basekit-seo:sitemap:warm`), registered in the service provider's `boot()`.
- `docs/` — VitePress documentation site (published to GitHub Pages). `guide/`
  is user-facing; `development/` is maintainer-only and excluded via
  `srcExclude` in `docs/.vitepress/config.mts`. When you add a guide page, add
  it to that sidebar too, or it will not be linked.
- `tests/` — Pest feature and unit tests running against Orchestra Testbench
  (11.*). Stubs used by tests live in `tests/TestSupport/`.

## Development commands

Install dependencies with `composer install`.

### Tests

```bash
composer test
```

### Coverage

```bash
composer test-coverage
```

Writes an HTML report to `build/coverage`. CI runs this with a `--min` floor on
PHP 8.4 using pcov. **Never lower the floor to make a build pass** — raise it to
the measured baseline instead. Note that no coverage driver is installed by
default in local environments, so this command only works where xdebug or pcov
is available.

### Code style (Laravel Pint)

```bash
composer format   # apply fixes
composer lint     # check only, as CI runs it
```

### Static analysis (PHPStan / Larastan)

```bash
composer analyse
```

### Dependency vulnerabilities

```bash
composer audit
```

CI fails on any advisory, including dev-only ones.

### Everything at once

```bash
composer check   # lint + analyse + test
```

## Package development

When working on this package, treat it as any other piece of distributed software: the
public API you expose today is a contract your consumers rely on.

### Configuration

Extend `config/basekit-laravel-seo.php` for new options, and always read them with `config('basekit-laravel-seo.key')`
using sensible defaults. Changes to publishable config go through the service provider's
`publishes` call with the `basekit-laravel-seo-config` tag.

`Support/ConfigValidator` runs from the service provider's `boot()` and rejects
values the package cannot render a correct result from. Add new constraints
there rather than letting them fail at request time — a misconfiguration should
fail the deploy, not a crawler. Values that legitimately depend on the request
(origin, hosts) must **not** be validated at boot; they fail at the point of use
instead.


### Views

Add Blade templates under `resources/views/`. Reference them from the consumer's app with the
namespace syntax `view('basekit-laravel-seo::name')`. Views should render standalone and never assume
the consumer's layout.



### Testing

Write Pest tests under `tests/`. Prefer Tests\TestCase when the test needs the framework
container; keep pure logic tests under `tests/Unit/`. Verify behavior from the consumer's
perspective where appropriate instead of asserting implementation details.

### Long-running workers

The package must stay correct under Octane and queue workers, which keep
container bindings alive across requests. When touching these services:

- `SeoManager` and `SitemapGenerator` are `scoped`, and `SitemapGenerator`
  additionally keys its memo on the current request object. Both layers matter:
  scoped bindings alone are not flushed between requests in every context.
- `SitemapChunker` and `SitemapRenderer` must stay stateless — keep per-run
  bookkeeping in locals inside a single call, never on `$this`.
- If you add a memo or cache to a service, decide how it is invalidated and prove
  it with a test. Silent stale reads are the failure mode to avoid.

### Caching and HTTP responses

Rendered sitemap documents are cached and served with `Cache-Control`, `ETag`,
`X-Content-Type-Options: nosniff` and `304` support via `Http/CacheableResponse`.
Two consequences to keep in mind:

- Sitemap output is cached, so **any change to providers or content needs a cache
  invalidation**. `SitemapCache::clear()` is the public API; the
  `basekit-seo:sitemap:clear` / `:warm` commands wrap it. If you add something
  that affects the sitemap, say so in the changelog.
- Changing the rendering of any document means the ETag changes, which is
  correct — but never weaken the caching headers to make a test pass.

## Compatibility

- Respect the Composer constraints in `composer.json`: PHP ^8.4|^8.5 and Laravel
  ^13. Do not introduce syntax, APIs, or dependencies that break the declared
  minimum versions.
- Classify dependencies correctly in `composer.json`: runtime needs go into `require`;
  development-only tooling goes into `require-dev`.
- Prefer requiring interfaces and small, well-maintained packages. Avoid adding a dependency
  where a few lines of stdlib or Illuminate code suffice.
- Package APIs are contracts. Avoid breaking changes; when they are unavoidable, follow the
  release workflow and document a migration path.
- New public classes, methods, config keys, and commands are public API — document them in the
  README and changelog.
- `CHANGELOG.md` is generated by release-please from conventional commits. Do not
  hand-edit the released sections; write clear conventional commit messages
  (`fix:`, `feat:`, `perf:`, `docs:`) instead.

## Agent rules

These rules apply to every AI agent working in this repository:

1. **Inspect first.** Before editing anything, read the relevant package structure, related
   classes, tests, configuration, Composer constraints, and existing patterns.
2. **Search before creating.** Before creating a new class, component, or config option, look
   for an existing equivalent.
3. **Prefer existing patterns.** Follow the package's existing architecture and conventions.
4. **Minimal changes.** Implement the smallest correct change that satisfies the request.
5. **Write tests.** Every meaningful behavior change should come with tests.
6. **Run the relevant checks.** Actually run the tests/analysis documented above, and the
   targeted subset when a full run is impractical.
7. **Format your changes.** Run the configured formatter on the files you touched.
8. **Inspect your diff.** Review what you changed before reporting completion.
9. **Do not modify generated or vendor files.** Files under `vendor/`, published resources that
   are not yours, and generated artifacts must never be hand-edited.
10. **Report honestly.** Never claim "tests pass" or "analysis is clean" unless you actually ran
    the commands and they succeeded.
11. **Keep docs in sync.** A new config key, command or public method needs an
    update in `README.md` and the matching page under `docs/guide/`, plus the
    VitePress sidebar if it is a new page.
12. **Keep commit messages short.** One conventional-commit subject line and
    nothing else: `feat:`, `fix:`, `perf:`, `docs:`, `ci:` or `chore:`. release-please
    turns these subjects into the changelog, so they are what users actually read. Put
    rationale, refactor notes and test plans in the pull request, not the commit body.