# basekit-laravel-seo

This package provides Basekit Laravel SEO is a reusable, optional feature package for centralized metadata, structured data (JSON-LD), robots directives and XML sitemaps on Basekit-powered Laravel websites..

It targets PHP ^8.3|^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
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



- `resources/views/` — Blade views exposed under the `basekit-laravel-seo` view namespace
  (`view('basekit-laravel-seo::view-name')`).




- `tests/` — Pest feature and unit tests running against Orchestra Testbench
  (11.*).

## Development commands

Install dependencies with `composer install`.

### Tests

```bash
composer test
```
### Code style (Laravel Pint)

```bash
composer format
```
### Static analysis (PHPStan / Larastan)

```bash
composer analyse
```




## Package development

When working on this package, treat it as any other piece of distributed software: the
public API you expose today is a contract your consumers rely on.

### Configuration

Extend `config/basekit-laravel-seo.php` for new options, and always read them with `config('basekit-laravel-seo.key')`
using sensible defaults. Changes to publishable config go through the service provider's
`publishes` call with the `basekit-laravel-seo-config` tag.



### Views

Add Blade templates under `resources/views/`. Reference them from the consumer's app with the
namespace syntax `view('basekit-laravel-seo::name')`. Views should render standalone and never assume
the consumer's layout.



### Testing

Write Pest tests under `tests/`. Prefer Tests\TestCase when the test needs the framework
container; keep pure logic tests under `tests/Unit/`. Verify behavior from the consumer's
perspective where appropriate instead of asserting implementation details.

## Compatibility

- Respect the Composer constraints in `composer.json`: PHP ^8.3|^8.4|^8.5 and Laravel
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