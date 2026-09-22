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
- Code style: `composer format` (Laravel Pint)
- Static analysis: `composer analyse` (PHPStan)

## Repository guardrails

- Never modify files under `vendor/` or generated/published artifacts.
- Inspect the relevant code first, search for existing equivalents before creating new
  components, and report verification results truthfully.