---
title: Feature roadmap (maintainer)
description: Internal planning document. Not a list of implemented features.
---

> **Maintainer context only.** This is an internal planning document — nothing
> here is implemented just because it is listed. For what the package actually
> does, read the [Guide](../guide/getting-started).

# Feature Roadmap — basekit-laravel-seo

> Companion to [Architecture audit](./architecture). Nothing here is implemented yet.
> Prioritisation: **P0** foundations, **P1** core functionality, **P2** useful improvements,
> **P3** future/optional.
> Every item explains *why it belongs in this package* and why it is at that level.

Legend — criteria used:
- **P0** unlocks the package's *reason for being* (metadata + rendering), closes a security hole, or
  protects the public API for the ecosystem.
- **P1** makes the promise (sitemap/robots) actually usable and consumable.
- **P2** makes it excellent (splitting, docs site, schema registry) without blocking v1.
- **P3** is speculative value we deliberately defer until ecosystem demand proves it.

---

## P0 — Architecture / foundation

### P0.1 Immutable `SeoData` value object
`final readonly` class holding title, description, canonical URL, robots directives, OpenGraph,
Twitter/X, locale, alternates, schemas; named constructors + withers; `toArray()`/`jsonSerialize()`.
**Why here:** it is the single contract every other feature (resolution, rendering, resolvers,
sitemap entries, ecosystem packages) speaks in terms of. Nothing can be built on top of a
consistent core until it exists.

### P0.2 `seo()` manager / helper + layered resolution pipeline
Container-backed `Seo` singleton, `seo()` helper (and facade), merging package defaults → site
defaults → page/route defaults → model-derived → explicit overrides, producing `SeoData`.
**Why here:** documented in the composer description ("centralized metadata") but missing; it is the
developer-facing API that makes the rest usable and is the exact behaviour `seo()->for($model)`
depends on.

### P0.3 `SeoResolver` contract
`supports(mixed $subject): bool` + `resolve(mixed $subject): ?SeoData`, discovered via a tagged
container binding. **Why here:** the *only* abstraction that lets blog/pages/content/e-commerce
integrate without this package knowing them — the core of ecosystem integration.

### P0.4 Head rendering component
`<x-basekit-laravel-seo::head/>` with per-group partial views (title/description/canonical/robots,
OG, Twitter, alternates, JSON-LD). **Why here:** there is currently no way to emit a single one of
these tags on a page; rendering is the package's primary job.

### P0.5 JSON-LD escaping hardening
`JSON_HEX_TAG` in `Schema::toJson()` so `</script>` in user strings cannot break out of
`application/ld+json`. **Why here:** a confirmed injection class (S1 in the audit) in existing
public code — must ship before any user-facing metadata rendering exists.

### P0.6 Canonical URL trust/normalisation helper
`CanonicalUrl`/`Url` resolving from configured base → `app.url` → request-with-trust, scheme
whitelist, no userinfo/fragments, host lowercasing, port/trailing-slash/dot-segment cleaning.
**Why here:** host-header poisoning is the core SEO security risk once canonical/alternates/sitemap
URLs are generated.

### P0.7 sitemap `loc`/robots escaping + validation
XML-aware sitemap escaping, single-line robots directives, URL scheme validation.
**Why here:** addresses audit findings S2/S4; correctness of emitted XML/robots is non-negotiable
once routes serve them.

### P0.8 Declare `illuminate/view` in `require`
**Why here:** truthfulness of the dependency graph; source already uses `Illuminate\View\View` and
the `view()` helper. Framework-native, keeps the no-third-party philosophy.

### P0.9 Scaffold the security + BC regression test matrix
**Why here:** the guarantees introduced in P0.5–P0.7 and the frozen public API need to be pinned by
tests from day one, before the surface grows.

### P0.10 Freeze current public API surface + deprecation policy page
Document the existing classes/methods/config keys as contracts.
**Why here:** the package is an ecosystem dependency; deviating without a policy is the highest-risk
architectural decision.

---

## P1 — Core functionality

### P1.1 `SitemapProvider` + `SitemapEntry` value object + aggregation
**Why here:** the audit's main architectural requirement — letting pages/blog/products/apps
contribute URLs without this package (or users) hard-joining Eloquent models. The existing
array-based `Sitemap` remains for BC and becomes one renderer on top of the registry.

### P1.2 Sitemap routes, index, and splitting
`/seo/sitemap.xml` (+ index + `sitemap-{n}.xml`), configurable, registered only when enabled.
**Why here:** publishing what providers produce is the deliverable; index/splitting is required
by the sitemap spec at scale.

### P1.3 Sitemap caching
Cache rendered sitemap/resolved entries, off by default, TTL + invalidation-friendly keys.
**Why here:** sitemap aggregation (DB-driven providers) is the one genuinely expensive path the
package owns; everything else (§14 of the audit) is premature caching.

### P1.4 robots.txt route + multi-agent groups + custom directives
**Why here:** config-driven directives already exist; the missing half is serving them dynamically
and letting apps choose via config whether the package owns `/robots.txt`. No hard-coded app rules:
a core promise of the audit.

### P1.5 Alternates / hreflang / `x-default` rendering
**Why here:** first-class `SeoData.alternates` + `<link rel="alternate">`; without it the package
cannot serve multilingual Basekit sites, and it must never auto-generate alternates (domain
knowledge) — so the render side is what belongs here.

### P1.6 Missing core schemas: `PersonSchema`, `BreadcrumbListSchema`
**Why here:** both were in the audit's "core" list (WebSite/WebPage/Organization/Article/Person/
BreadcrumbList) but only four of six exist; Article pages depend on publisher/author (Person) and
WebPage breadcrumbs deserve their own class rather than being inlined.

### P1.7 README + `docs/` skeleton (installation, quickstart, security)
**Why here:** with P0/P1 the API becomes public and consumable; a package that exists silently is
adopted by nobody (neither humans nor sibling Basekit packages).

### P1.8 Feature tests for routes and the head component
**Why here:** `tests/Feature` is empty; HTTP-level tests (sitemap endpoint, robots endpoint, head
rendering) are the consumer-perspective coverage AGENTS.md asks for.

---

## P2 — Useful improvements

### P2.1 Site-wide `StructuredDataProvider` (tagged registry)
**Why here:** only needed once sites consistently render Organization/WebSite on *every* page
independent of a per-page resolver. Audit defers it (composition-first for schemas) — this keeps P1
lean while still giving the ecosystem an explicit extension point.

### P2.2 Optional storage companion evaluation (Option C)
**Why here:** audit's storage recommendation is "storage stays out of core (Option A)" with a
future optional companion for central polymorphic SEO editing. Build it here only if ecosystem
demand materialises; keeping it out of P0/P1 preserves portability.

### P2.3 Sitemap cache-management niceties
Command to warm/clear the sitemap cache, `Cache::tags` when available.
**Why here:** improves operator experience for large sites; not required for a correct v1.

### P2.4 Public docs site (GitHub Pages) + generated API reference
**Why here:** "similar quality to Basekit UI" documentation was explicitly requested; a static
site with per-package integration recipes is the ecosystem-scale deliverable.

### P2.5 Twitter/OG advanced fallback configuration
Configurable fallback order (title→og→twitter), image sizing defaults.
**Why here:** polish that reduces duplication across consumer pages; cheap once SeoData exists.

### P2.6 Route/PHPUnit-level escape-test expansion
Proptest-style fuzzing of loc/title values in sitemap and head output.
**Why here:** cheap insurance on top of P0.9; good fit for the periodic security review.

---

## P3 — Future / optional

### P3.1 `@seoHead` Blade directive sugar
**Why here:** ergonomics only; audit recommends the component as the sole initial surface. Add only
if the ecosystem requests it — avoids two rendering paths to maintain.

### P3.2 Admin / editing UI for SEO metadata
**Why here:** would tempt Livewire/Filament/Basekit-UI dependencies, which the audit explicitly
excludes from the core. Belongs in a separate package so ordinary Laravel apps stay dependency-free.

### P3.3 Locale detection helpers
**Why here:** only if ecosystem demand proves it; otherwise alternates should keep being
consumer-registered (domain knowledge).

### P3.4 Advanced schema primitives (FAQ, Product, Review, …)
**Why here:** making the package a broad schema catalogue contradicts its "small core" mandate;
schemas beyond the six core types belong to the domain packages that need them.

### P3.5 Async/push rendering integrations (Livewire/Filament segments)
**Why here:** speculative; requires the ecosystem partners to exist and prove a need before adding
bridge code to this package.

---

## Suggested slicing by release

| Release | Contains |
|---|---|
| 1.1 (next, P0) | SeoData + `seo()`/resolver + head component + escaping fixes + `Url` helper + deps fix + tests |
| 1.2–1.3 (P1) | sitemap providers/routes/index/caching, robots route, alternates, core schemas, README/docs skeleton |
| 1.x+ (P2) | schema registry, cache mgmt, docs site, fallback config |
| Later (P3) | everything deferred, gated on ecosystem proof |

Each slicing decision is recorded so future contributors know *why* an item is where it is and what
would move it up — per the audit's "for every recommendation explain why it belongs in this
package".