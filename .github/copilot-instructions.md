# GitHub Copilot Instructions

This repository is a Laravel package: `basekit-laravel-seo` — Basekit Laravel SEO is a reusable, optional feature package for centralized metadata, structured data (JSON-LD), robots directives and XML sitemaps on Basekit-powered Laravel websites..

It targets PHP ^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
`basekit-laravel/basekit-laravel-seo`. The canonical agent instructions live in `AGENTS.md` — if you
can read that file, prefer it over these instructions. This file exists so Copilot surfaces
that only read `copilot-instructions.md` (for example code review) still follow project rules.

## Working in this repository

- This is a **Laravel package**, not a standalone Laravel application. Never assume
  application-only scaffolding such as `app/`, authentication, `.env`, or a local `config/app.php`.
- Respect the Composer constraints in `composer.json` (PHP ^8.4|^8.5, Laravel ^13)
  and classify runtime vs development dependencies correctly (`require` vs `require-dev`).
- Package APIs (public classes, methods, config keys, commands) are contracts — avoid breaking
  changes and document the public behavior.
- Prefer existing package patterns; implement the smallest correct change.
- Require tests for meaningful behavior changes and document their status honestly.

## Verification commands (use only those configured in composer.json)

- Tests: `composer test`
- Coverage: `composer test-coverage` (HTML report in `build/coverage`; needs a
  coverage driver, which CI provides)
- Code style: `composer lint` (check, as CI runs it) / `composer format` (apply fixes)
- Static analysis: `composer analyse` (PHPStan/Larastan)
- Vulnerabilities: `composer audit`
- All of the above except coverage: `composer check`

## Correctness expectations

- Long-running workers (Octane, queue) keep bindings alive. `SeoManager` and
  `SitemapGenerator` are `scoped`; `SitemapChunker` and `SitemapRenderer` must stay
  stateless. Never introduce per-request state on a shared singleton.
- Rendered sitemaps are cached and served with `Cache-Control`/`ETag`/`304`. Any change
  to provider output needs a cache invalidation, and rendering changes legitimately
  change the ETag — do not weaken caching headers to make a test pass.
- Nothing may be built from the raw `Host` header; URLs come from a trusted canonical
  origin.

## Commits

Write a single conventional-commit subject line (`feat:`, `fix:`, `perf:`, `docs:`,
`ci:`, `chore:`) and no commit body. release-please generates the changelog from
those subjects, so keep them short and put rationale in the pull request.

## Repository guardrails

- Never modify files under `vendor/` or generated/published artifacts.
- Inspect the relevant code first, search for existing equivalents before creating new
  components, and report verification results truthfully.