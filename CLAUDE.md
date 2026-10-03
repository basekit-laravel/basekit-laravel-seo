# Claude Code Instructions

This repository contains a Laravel package (`basekit-laravel-seo`) that provides
`Basekit Laravel SEO is a reusable, optional feature package for centralized metadata, structured data (JSON-LD), robots directives and XML sitemaps on Basekit-powered Laravel websites.`. The canonical project instructions live in `AGENTS.md` and always take
precedence over anything in this file.

@AGENTS.md

## Claude Code specifics

- The canonical instructions for this package are in `AGENTS.md` — if it is present, read it
  before starting work and follow it.
- Follow the "Agent rules" section of `AGENTS.md` in every session.
- Run the package's configured verification commands (tests, formatter, static analysis)
  exactly as documented in `AGENTS.md` and report the real results.
- Never modify files under `vendor/` or generated/published artifacts.
- Prefer the package's existing patterns over introducing new ones; keep changes minimal.
- The verification commands are `composer lint`, `composer analyse`, `composer test`,
  `composer test-coverage` and `composer audit`. `composer check` runs lint, analyse and
  test together.
- Sitemap output is cached. If you change anything that affects the rendered sitemap,
  the cache needs invalidating (`app(SitemapCache::class)->clear()`,
  `basekit-seo:sitemap:clear`) — say so in your summary.
- Keep per-request services Octane-safe: `SeoManager`/`SitemapGenerator` are `scoped`,
  `SitemapChunker`/`SitemapRenderer` must stay stateless.
- Write commit messages as a single conventional-commit subject line (`feat:`, `fix:`,
  `perf:`, `docs:`, `ci:`, `chore:`) with no body. release-please uses those subjects for
  the changelog; put rationale in the pull request instead.